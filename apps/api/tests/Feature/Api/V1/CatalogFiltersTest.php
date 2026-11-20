<?php

use App\Enums\ProductType;
use App\Models\ActivationService;
use App\Models\Platform;
use App\Models\Region;

it('lists the filter options sorted by name ignoring case, and the product types', function () {
    Platform::factory()->create(['name' => 'Xbox Series', 'slug' => 'xbox-series']);
    Platform::factory()->create(['name' => 'PS5', 'slug' => 'ps5']);
    Platform::factory()->create(['name' => 'macOS', 'slug' => 'macos']);
    ActivationService::factory()->create(['name' => 'Steam', 'slug' => 'steam']);
    ActivationService::factory()->create(['name' => 'Nintendo eShop', 'slug' => 'nintendo-eshop']);
    Region::factory()->create(['name' => 'LATAM', 'slug' => 'latam']);
    Region::factory()->create(['name' => 'Global', 'slug' => 'global']);

    $this->getJson(route('api.v1.catalog.filters'))
        ->assertOk()
        ->assertExactJson(['data' => [
            'platforms' => [
                ['slug' => 'macos', 'name' => 'macOS'],
                ['slug' => 'ps5', 'name' => 'PS5'],
                ['slug' => 'xbox-series', 'name' => 'Xbox Series'],
            ],
            'types' => array_map(
                fn (ProductType $type) => ['slug' => $type->value, 'name' => $type->label()],
                ProductType::cases(),
            ),
            'activation_services' => [
                ['slug' => 'nintendo-eshop', 'name' => 'Nintendo eShop'],
                ['slug' => 'steam', 'name' => 'Steam'],
            ],
            'regions' => [
                ['slug' => 'global', 'name' => 'Global'],
                ['slug' => 'latam', 'name' => 'LATAM'],
            ],
        ]]);
});
