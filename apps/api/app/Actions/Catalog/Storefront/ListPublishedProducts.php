<?php

namespace App\Actions\Catalog\Storefront;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

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
        $query = Product::query()
            ->storefront()
            ->when($filters->platforms !== [], fn (Builder $query) => $query->whereHas(
                'platforms',
                fn (Builder $platforms) => $platforms->whereIn('platforms.slug', $filters->platforms),
            ))
            ->when($filters->types !== [], fn (Builder $query) => $query->whereIn('products.type', $filters->types))
            ->when($filters->activationServices !== [], fn (Builder $query) => $query->whereHas(
                'activationService',
                fn (Builder $services) => $services->whereIn('slug', $filters->activationServices),
            ))
            ->when($filters->regions !== [], fn (Builder $query) => $query->whereHas(
                'region',
                fn (Builder $regions) => $regions->whereIn('slug', $filters->regions),
            ));

        $filters->sort->apply($query);

        return $query->paginate(self::PER_PAGE);
    }
}
