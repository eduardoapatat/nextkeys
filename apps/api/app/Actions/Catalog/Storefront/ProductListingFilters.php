<?php

namespace App\Actions\Catalog\Storefront;

use App\Enums\ProductSort;
use App\Enums\ProductType;

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
}
