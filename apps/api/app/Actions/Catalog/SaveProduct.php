<?php

namespace App\Actions\Catalog;

use App\Enums\ProductType;
use App\Models\ActivationService;
use App\Models\Platform;
use App\Models\Product;
use App\Models\Region;
use App\Models\Title;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Validates and writes a product with its platforms. Shared by CreateProduct
 * and UpdateProduct; call those instead.
 *
 * The rules mirror the database CHECKs and triggers of docs/domain/catalog.md
 * so the admin gets readable errors. The database stays the safety net: its
 * constraint triggers are checked before the transaction ends and their
 * violations are turned into the same validation errors.
 *
 * The cover is written to the `public` disk before the transaction, because
 * files are not transactional: a failed save deletes the new file, and the
 * replaced or removed one is only deleted once the save has committed.
 */
final class SaveProduct
{
    private const int MAX_INTEGER = 2147483647;

    public function __construct(private readonly GenerateProductSlug $slugs) {}

    /**
     * @throws ValidationException
     */
    public function handle(Product $product, ProductType $type, ProductData $data): Product
    {
        $this->validate($product, $type, $data);
        $this->ensureIdentityIsFree($product, $type, $data);

        $previousCover = $product->cover_image;
        $newCover = $data->cover instanceof UploadedFile ? $this->storeCover($data->cover) : null;
        $cover = $newCover ?? ($data->removeCover ? null : $previousCover);

        try {
            $saved = $this->write($product, $type, $data, $cover);
        } catch (Throwable $exception) {
            if ($newCover !== null) {
                Storage::disk('public')->delete($newCover);
            }

            throw $exception;
        }

        if ($previousCover !== null && $previousCover !== $cover) {
            Storage::disk('public')->delete($previousCover);
        }

        return $saved;
    }

    /**
     * @throws ValidationException
     */
    private function write(Product $product, ProductType $type, ProductData $data, ?string $cover): Product
    {
        try {
            return DB::transaction(function () use ($product, $type, $data, $cover): Product {
                $product->fill([
                    'cover_image' => $cover,
                    'title_id' => $data->titleId,
                    'activation_service_id' => $data->activationServiceId,
                    'region_id' => $data->regionId,
                    'price' => $data->price,
                    'stock' => $data->stock,
                    'status' => $data->status,
                    'slug' => $data->slug ?? $product->slug ?? $this->generatedSlug($type, $data),
                ]);

                if (! $product->exists) {
                    $product->type = $type;
                }

                $product->save();
                $product->platforms()->sync($data->platformIds);

                DB::statement('SET CONSTRAINTS products_identity, product_platforms_identity IMMEDIATE');
                DB::statement('SET CONSTRAINTS products_identity, product_platforms_identity DEFERRED');

                return $product;
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (str_contains($exception->getMessage(), 'products_slug_unique')) {
                throw ValidationException::withMessages(['slug' => 'Ese slug ya está en uso.']);
            }

            throw ValidationException::withMessages(['product' => 'Este producto ya existe.']);
        }
    }

    /**
     * @throws ValidationException
     */
    private function validate(Product $product, ProductType $type, ProductData $data): void
    {
        $deliveredByKey = $type->isDeliveredByKey();

        Validator::make([
            'title_id' => $data->titleId,
            'activation_service_id' => $data->activationServiceId,
            'region_id' => $data->regionId,
            'platform_ids' => $data->platformIds,
            'price' => $data->price,
            'stock' => $data->stock,
            'slug' => $data->slug,
            'cover' => $data->cover,
        ], [
            'title_id' => ['required', Rule::exists(Title::class, 'id')],
            'activation_service_id' => [$deliveredByKey ? 'required' : 'nullable', Rule::exists(ActivationService::class, 'id')],
            'region_id' => [$deliveredByKey ? 'required' : 'nullable', Rule::exists(Region::class, 'id')],
            'platform_ids' => ['required', 'array', 'min:1', ...($deliveredByKey ? [] : ['max:1'])],
            'platform_ids.*' => ['distinct', Rule::exists(Platform::class, 'id')],
            'price' => ['integer', 'min:1', 'max:'.self::MAX_INTEGER],
            'stock' => $deliveredByKey ? ['prohibited'] : ['required', 'integer', 'min:0', 'max:'.self::MAX_INTEGER],
            'slug' => ['nullable', 'max:255', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', Rule::unique(Product::class, 'slug')->ignore($product->id)],
            'cover' => ['nullable', 'bail', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=300,min_height=400'],
        ], [
            'platform_ids.max' => 'Un producto físico cubre exactamente una plataforma.',
            'stock.prohibited' => 'Para este tipo, el stock se cuenta a partir de las claves disponibles.',
            'slug.regex' => 'Usa minúsculas, números y guiones simples.',
            'cover.max' => 'La carátula no puede pesar más de 2 MB.',
        ])->validate();
    }

    private function storeCover(UploadedFile $cover): string
    {
        $path = Storage::disk('public')->putFile('covers', $cover);

        if ($path === false) {
            throw new \RuntimeException('The product cover could not be stored.');
        }

        return $path;
    }

    /**
     * Rejects a second product with the same title, type, activation service,
     * region and exact set of platforms (the identity trigger's rule).
     *
     * @throws ValidationException
     */
    private function ensureIdentityIsFree(Product $product, ProductType $type, ProductData $data): void
    {
        $platformCount = count($data->platformIds);

        $existing = Product::query()
            ->where('title_id', $data->titleId)
            ->where('type', $type)
            ->when(
                $data->activationServiceId,
                fn ($query, int $id) => $query->where('activation_service_id', $id),
                fn ($query) => $query->whereNull('activation_service_id'),
            )
            ->when(
                $data->regionId,
                fn ($query, int $id) => $query->where('region_id', $id),
                fn ($query) => $query->whereNull('region_id'),
            )
            ->when($product->exists, fn ($query) => $query->whereKeyNot($product->id))
            ->has('platforms', '=', $platformCount)
            ->whereHas('platforms', fn ($query) => $query->whereIn('platforms.id', $data->platformIds), '=', $platformCount)
            ->first();

        if ($existing !== null) {
            throw ValidationException::withMessages([
                'product' => "Este producto ya existe: {$existing->slug}.",
            ]);
        }
    }

    private function generatedSlug(ProductType $type, ProductData $data): string
    {
        $suggestion = $this->slugs->suggest(
            Title::query()->findOrFail($data->titleId),
            $type,
            $data->activationServiceId === null ? null : ActivationService::query()->findOrFail($data->activationServiceId),
            Platform::query()->whereKey($data->platformIds)->get(),
            $data->regionId === null ? null : Region::query()->findOrFail($data->regionId),
        );

        return $this->slugs->unique($suggestion);
    }
}
