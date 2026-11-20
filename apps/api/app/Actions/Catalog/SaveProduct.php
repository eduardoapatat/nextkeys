<?php

namespace App\Actions\Catalog;

use App\Enums\ProductType;
use App\Models\ActivationService;
use App\Models\Platform;
use App\Models\Product;
use App\Models\Region;
use App\Models\Title;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Validates and writes a product with its platforms. Shared by CreateProduct
 * and UpdateProduct; call those instead.
 *
 * The rules mirror the database CHECKs and triggers of docs/domain/catalog.md
 * so the admin gets readable errors. The database stays the safety net: its
 * constraint triggers are checked before the transaction ends and their
 * violations are turned into the same validation errors.
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

        try {
            return DB::transaction(function () use ($product, $type, $data): Product {
                $product->fill([
                    'title_id' => $data->titleId,
                    'activation_service_id' => $data->activationServiceId,
                    'region_id' => $data->regionId,
                    'price' => $data->price,
                    'stock' => $data->stock,
                    'is_published' => $data->isPublished,
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
        ], [
            'title_id' => ['required', Rule::exists(Title::class, 'id')],
            'activation_service_id' => [$deliveredByKey ? 'required' : 'nullable', Rule::exists(ActivationService::class, 'id')],
            'region_id' => [$deliveredByKey ? 'required' : 'nullable', Rule::exists(Region::class, 'id')],
            'platform_ids' => ['required', 'array', 'min:1', ...($deliveredByKey ? [] : ['max:1'])],
            'platform_ids.*' => ['distinct', Rule::exists(Platform::class, 'id')],
            'price' => ['integer', 'min:1', 'max:'.self::MAX_INTEGER],
            'stock' => $deliveredByKey ? ['prohibited'] : ['required', 'integer', 'min:0', 'max:'.self::MAX_INTEGER],
            'slug' => ['nullable', 'max:255', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', Rule::unique(Product::class, 'slug')->ignore($product->id)],
        ], [
            'platform_ids.max' => 'Un producto físico cubre exactamente una plataforma.',
            'stock.prohibited' => 'Para este tipo, el stock se cuenta a partir de las keys disponibles.',
            'slug.regex' => 'Usa minúsculas, números y guiones simples.',
        ])->validate();
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
