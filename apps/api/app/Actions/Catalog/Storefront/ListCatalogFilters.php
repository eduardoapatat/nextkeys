<?php

namespace App\Actions\Catalog\Storefront;

use App\Enums\ProductType;
use App\Models\ActivationService;
use App\Models\Platform;
use App\Models\Region;

final class ListCatalogFilters
{
    /**
     * Options of each listing filter, as `{slug, name}` pairs sorted by name
     * ignoring case (the database collation puts `macOS` after `Xbox`).
     * Product types come from the enum, in declaration order.
     *
     * @return array{
     *     platforms: list<array{slug: string, name: string}>,
     *     types: list<array{slug: string, name: string}>,
     *     activation_services: list<array{slug: string, name: string}>,
     *     regions: list<array{slug: string, name: string}>,
     * }
     */
    public function handle(): array
    {
        return [
            'platforms' => $this->options(Platform::class),
            'types' => array_map(
                fn (ProductType $type): array => ['slug' => $type->value, 'name' => $type->label()],
                ProductType::cases(),
            ),
            'activation_services' => $this->options(ActivationService::class),
            'regions' => $this->options(Region::class),
        ];
    }

    /**
     * @param  class-string<Platform|ActivationService|Region>  $model
     * @return list<array{slug: string, name: string}>
     */
    private function options(string $model): array
    {
        return array_values($model::query()
            ->orderByRaw('lower(name)')
            ->orderBy('id')
            ->get(['slug', 'name'])
            ->map(fn (Platform|ActivationService|Region $option): array => ['slug' => $option->slug, 'name' => $option->name])
            ->all());
    }
}
