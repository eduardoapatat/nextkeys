<?php

namespace App\Http\Controllers\Catalog;

use App\Actions\Catalog\CreateProduct;
use App\Actions\Catalog\DeleteProduct;
use App\Actions\Catalog\UpdateProduct;
use App\DataTables\ProductsTable;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreProductRequest;
use App\Http\Requests\Catalog\UpdateProductRequest;
use App\Models\ActivationService;
use App\Models\Platform;
use App\Models\Product;
use App\Models\Region;
use App\Models\Title;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('catalog/products/Index', [
            'products' => fn () => ProductsTable::make(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('catalog/products/Create', $this->formOptions());
    }

    public function store(StoreProductRequest $request, CreateProduct $createProduct): RedirectResponse
    {
        $product = $createProduct->handle($request->type(), $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Producto {$product->slug} creado."]);

        return to_route('products.index');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('catalog/products/Edit', [
            ...$this->formOptions(),
            'product' => [
                'id' => $product->id,
                'type' => $product->type->value,
                'title_id' => $product->title_id,
                'activation_service_id' => $product->activation_service_id,
                'region_id' => $product->region_id,
                'platform_ids' => $product->platforms()->pluck('platforms.id')->all(),
                'price' => $product->price,
                'stock' => $product->stock,
                'status' => $product->status->value,
                'slug' => $product->slug,
                'cover_url' => $product->coverUrl(),
                'cover_band' => $product->cover_band,
            ],
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product, UpdateProduct $updateProduct): RedirectResponse
    {
        $updateProduct->handle($product, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Producto actualizado.']);

        return to_route('products.index');
    }

    public function destroy(Product $product, DeleteProduct $deleteProduct): RedirectResponse
    {
        $deleteProduct->handle($product);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Producto eliminado.']);

        return to_route('products.index');
    }

    /**
     * The choices of the product form, as `{value, label}` lists.
     *
     * @return array<string, list<array{value: int|string, label: string}>>
     */
    private function formOptions(): array
    {
        return [
            'types' => array_map(
                fn (ProductType $type): array => ['value' => $type->value, 'label' => $type->label()],
                ProductType::cases(),
            ),
            'statuses' => array_map(
                fn (ProductStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                ProductStatus::cases(),
            ),
            'titles' => $this->options(Title::query()),
            'activationServices' => $this->options(ActivationService::query()),
            'regions' => $this->options(Region::query()),
            'platforms' => $this->options(Platform::query()),
        ];
    }

    /**
     * @param  Builder<Title>|Builder<ActivationService>|Builder<Region>|Builder<Platform>  $query
     * @return list<array{value: int, label: string}>
     */
    private function options(Builder $query): array
    {
        return array_values($query->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Title|ActivationService|Region|Platform $row): array => ['value' => $row->id, 'label' => $row->name])
            ->all());
    }
}
