<?php

namespace App\Actions\Catalog\Storefront;

use App\Enums\ListingFacet;
use App\Enums\ProductType;
use App\Models\ActivationService;
use App\Models\Platform;
use App\Models\Product;
use App\Models\Region;
use Illuminate\Database\Eloquent\Builder;

final class ListCatalogFilters
{
    /**
     * Options of each listing filter, as `{slug, name, count}` sorted by name
     * ignoring case (the database collation puts `macOS` after `Xbox`).
     * Product types come from the enum, in declaration order.
     *
     * `count` is how many published products the option would list with the
     * other facets applied: a facet's own selection is left out, because its
     * values are alternatives (PS4 or PS5). The storefront hides options that
     * count zero, so no combination it offers is empty. One grouped query per
     * facet: a fixed number of queries whatever the data.
     *
     * @return array{
     *     platforms: list<array{slug: string, name: string, count: int}>,
     *     types: list<array{slug: string, name: string, count: int}>,
     *     activation_services: list<array{slug: string, name: string, count: int}>,
     *     regions: list<array{slug: string, name: string, count: int}>,
     * }
     */
    public function handle(ProductListingFilters $filters = new ProductListingFilters): array
    {
        $types = $this->counts($filters, ListingFacet::Type, fn (Builder $query) => $query, 'products.type');

        return [
            'platforms' => $this->options(Platform::class, $this->counts(
                $filters,
                ListingFacet::Platform,
                fn (Builder $query) => $query
                    ->join('product_platforms', 'product_platforms.product_id', '=', 'products.id')
                    ->join('platforms', 'platforms.id', '=', 'product_platforms.platform_id'),
                'platforms.slug',
            )),
            'types' => array_map(
                fn (ProductType $type): array => [
                    'slug' => $type->value,
                    'name' => $type->label(),
                    'count' => $types[$type->value] ?? 0,
                ],
                ProductType::cases(),
            ),
            'activation_services' => $this->options(ActivationService::class, $this->counts(
                $filters,
                ListingFacet::ActivationService,
                fn (Builder $query) => $query->join('activation_services', 'activation_services.id', '=', 'products.activation_service_id'),
                'activation_services.slug',
            )),
            'regions' => $this->options(Region::class, $this->counts(
                $filters,
                ListingFacet::Region,
                fn (Builder $query) => $query->join('regions', 'regions.id', '=', 'products.region_id'),
                'regions.slug',
            )),
        ];
    }

    /**
     * Published products per option of one facet, matching the other facets.
     *
     * @param  callable(Builder<Product>): Builder<Product>  $join
     * @return array<string, int>
     */
    private function counts(ProductListingFilters $filters, ListingFacet $facet, callable $join, string $column): array
    {
        $rows = $join($filters->apply(Product::query()->published(), except: $facet))
            ->toBase()
            ->groupBy($column)
            ->select("{$column} as option")
            ->selectRaw('count(distinct products.id) as total')
            ->get();

        $counts = [];
        foreach ($rows as $row) {
            /** @var object{option: string, total: int|string} $row */
            $counts[(string) $row->option] = (int) $row->total;
        }

        return $counts;
    }

    /**
     * @param  class-string<Platform|ActivationService|Region>  $model
     * @param  array<string, int>  $counts
     * @return list<array{slug: string, name: string, count: int}>
     */
    private function options(string $model, array $counts): array
    {
        return array_values($model::query()
            ->orderByRaw('lower(name)')
            ->orderBy('id')
            ->get(['slug', 'name'])
            ->map(fn (Platform|ActivationService|Region $option): array => [
                'slug' => $option->slug,
                'name' => $option->name,
                'count' => $counts[$option->slug] ?? 0,
            ])
            ->all());
    }
}
