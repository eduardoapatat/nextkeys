<?php

namespace App\DataTables;

use App\Enums\ProductKeyStatus;
use App\Models\Product;
use App\Models\ProductKey;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InertiaDataTable\Column;
use InertiaDataTable\DataTable;

/**
 * The keys of one product in the admin panel: status and import date only.
 * The query selects neither `code` nor `code_hash`, so codes never leave the
 * database for a listing.
 */
class ProductKeysTable extends DataTable
{
    public function __construct(private readonly Product $product) {}

    /**
     * @return HasMany<ProductKey, Product>
     */
    public function query(): HasMany
    {
        return $this->product->keys()->select(['id', 'product_id', 'status', 'created_at']);
    }

    /**
     * @return list<Column>
     */
    public function columns(): array
    {
        return [
            Column::option('status', 'Estado')->options(ProductKeyStatus::class)->badge()->sortable()->filterSelect(),
            Column::date('created_at', 'Importada')->format('datetime')->sortable(),
        ];
    }

    /**
     * @return list<string>
     */
    public function alwaysInclude(): ?array
    {
        return ['id'];
    }

    public function defaultSort(): ?string
    {
        return '-created_at';
    }
}
