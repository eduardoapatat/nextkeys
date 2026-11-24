<?php

namespace App\Actions\Catalog\Storefront;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListPublishedProducts
{
    public const int PER_PAGE = 24;

    /**
     * Published products matching the filters, with everything the listing
     * shows loaded up front (no query per product).
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function handle(ProductListingFilters $filters): LengthAwarePaginator
    {
        $query = $filters->apply(Product::query()->storefront());

        $filters->sort->apply($query);

        return $query->paginate(self::PER_PAGE);
    }
}
