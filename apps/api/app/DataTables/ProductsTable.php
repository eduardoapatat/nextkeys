<?php

namespace App\DataTables;

use App\Enums\ProductKeyStatus;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductKey;
use Illuminate\Database\Eloquent\Builder;
use InertiaDataTable\Column;
use InertiaDataTable\DataTable;

/**
 * Products of the admin panel. The only filter is the status, to find drafts
 * and archived products (docs/domain/catalog.md, "Admin panel").
 */
class ProductsTable extends DataTable
{
    /**
     * Adds `available_stock`, the units that can be sold now, in the same
     * query (Product::availableStock() would run one query per row). The
     * `products_stock_only_for_physical` CHECK makes `stock` null exactly for
     * key-delivered types, so it falls back to their count of available keys.
     *
     * @return Builder<Product>
     */
    public function query(): Builder
    {
        return Product::query()
            ->select('products.*')
            ->selectSub(
                ProductKey::query()
                    ->selectRaw('coalesce(products.stock, count(*))')
                    ->whereColumn('product_keys.product_id', 'products.id')
                    ->where('status', ProductKeyStatus::Available),
                'available_stock',
            )
            ->withCasts(['available_stock' => 'integer']);
    }

    /**
     * @return list<Column>
     */
    public function columns(): array
    {
        return [
            Column::text('title', 'Título')->from('title.name')->sortable()->hideable(false),
            Column::option('type', 'Tipo')->options(ProductType::class)->badge()->sortable(),
            Column::text('activation_service', 'Servicio')->from('activationService.name')->sortable(),
            Column::text('region', 'Región')->from('region.name')->sortable(),
            Column::option('platforms', 'Plataformas')->from('platforms.name')->badge(),
            Column::number('price', 'Precio')->money('CLP')->sortable(),
            Column::number('available_stock', 'Stock'),
            Column::option('status', 'Estado')->options(ProductStatus::class)->badge()->sortable()->filterMultiSelect(),
            Column::text('slug', 'Slug')->hidden(),
        ];
    }

    /**
     * The row actions need the slug (to name the product) and the type (to
     * link the keys of key-delivered products) even when their columns are hidden.
     *
     * @return list<string>
     */
    public function alwaysInclude(): ?array
    {
        return ['id', 'slug', 'type'];
    }

    public function defaultSort(): ?string
    {
        return '-created_at';
    }
}
