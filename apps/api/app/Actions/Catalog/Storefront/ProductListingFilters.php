<?php

namespace App\Actions\Catalog\Storefront;

use App\Enums\ListingFacet;
use App\Enums\ProductSort;
use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

/**
 * Validated query of the storefront listing. Each list holds slugs (or types)
 * of one facet: values within a facet are OR, facets are AND.
 */
final readonly class ProductListingFilters
{
    /**
     * @param  list<string>  $platforms
     * @param  list<ProductType>  $types
     * @param  list<string>  $activationServices
     * @param  list<string>  $regions
     */
    public function __construct(
        public array $platforms = [],
        public array $types = [],
        public array $activationServices = [],
        public array $regions = [],
        public ProductSort $sort = ProductSort::Newest,
    ) {}

    /**
     * Restrict a product query to these filters, the one place that defines
     * what each facet matches. `$except` leaves one facet out, so the filter
     * options can count each facet against the other ones (a facet's own
     * values are alternatives, not restrictions).
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function apply(Builder $query, ?ListingFacet $except = null): Builder
    {
        $on = fn (ListingFacet $facet, array $values): bool => $facet !== $except && $values !== [];

        return $query
            ->when($on(ListingFacet::Platform, $this->platforms), fn (Builder $query) => $query->whereHas(
                'platforms',
                fn (Builder $platforms) => $platforms->whereIn('platforms.slug', $this->platforms),
            ))
            ->when($on(ListingFacet::Type, $this->types), fn (Builder $query) => $query->whereIn('products.type', $this->types))
            ->when($on(ListingFacet::ActivationService, $this->activationServices), fn (Builder $query) => $query->whereHas(
                'activationService',
                fn (Builder $services) => $services->whereIn('slug', $this->activationServices),
            ))
            ->when($on(ListingFacet::Region, $this->regions), fn (Builder $query) => $query->whereHas(
                'region',
                fn (Builder $regions) => $regions->whereIn('slug', $this->regions),
            ));
    }
}
