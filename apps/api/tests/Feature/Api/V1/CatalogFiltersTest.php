<?php

use App\Enums\ProductType;
use App\Models\ActivationService;
use App\Models\Platform;
use App\Models\Product;
use App\Models\Region;
use Illuminate\Support\Facades\DB;

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
                ['slug' => 'macos', 'name' => 'macOS', 'count' => 0],
                ['slug' => 'ps5', 'name' => 'PS5', 'count' => 0],
                ['slug' => 'xbox-series', 'name' => 'Xbox Series', 'count' => 0],
            ],
            'types' => array_map(
                fn (ProductType $type) => ['slug' => $type->value, 'name' => $type->label(), 'count' => 0],
                ProductType::cases(),
            ),
            'activation_services' => [
                ['slug' => 'nintendo-eshop', 'name' => 'Nintendo eShop', 'count' => 0],
                ['slug' => 'steam', 'name' => 'Steam', 'count' => 0],
            ],
            'regions' => [
                ['slug' => 'global', 'name' => 'Global', 'count' => 0],
                ['slug' => 'latam', 'name' => 'LATAM', 'count' => 0],
            ],
        ]]);
});

/**
 * `count` of each option of one facet, by slug.
 *
 * @param  array<string, string>  $query
 * @return array<string, int>
 */
function facetCounts(string $facet, array $query = []): array
{
    /** @var list<array{slug: string, count: int}> $options */
    $options = test()->getJson(route('api.v1.catalog.filters', $query))->assertOk()->json("data.{$facet}");

    return array_column($options, 'count', 'slug');
}

it('counts the published products of each option, a multiplatform product once per platform', function () {
    $ps4 = Platform::factory()->create(['slug' => 'ps4']);
    $ps5 = Platform::factory()->create(['slug' => 'ps5']);
    Product::factory()->published()->withPlatforms($ps4, $ps5)->create();
    Product::factory()->published()->withPlatforms($ps5)->create();
    Product::factory()->withPlatforms($ps5)->create();
    Product::factory()->archived()->withPlatforms($ps5)->create();

    expect(facetCounts('platforms'))->toMatchArray(['ps4' => 1, 'ps5' => 2])
        ->and(facetCounts('types'))->toBe(['digital_key' => 2, 'license' => 0, 'physical' => 0]);
});

it('counts each facet against the other facets, never against itself', function () {
    $steam = ActivationService::factory()->create(['slug' => 'steam']);
    $psStore = ActivationService::factory()->create(['slug' => 'playstation-store']);
    $windows = Platform::factory()->create(['slug' => 'windows']);
    $android = Platform::factory()->create(['slug' => 'android']);
    $ps4 = Platform::factory()->create(['slug' => 'ps4']);
    $ps5 = Platform::factory()->create(['slug' => 'ps5']);
    Product::factory()->published()->withPlatforms($windows)->create(['activation_service_id' => $steam->id]);
    Product::factory()->published()->withPlatforms($android, $windows)->license()->create();
    Product::factory()->published()->withPlatforms($ps5)->create(['activation_service_id' => $psStore->id]);
    Product::factory()->published()->withPlatforms($ps4)->create(['activation_service_id' => $psStore->id]);

    // With Steam chosen, only platforms Steam products have count.
    expect(facetCounts('platforms', ['activation_service' => 'steam']))
        ->toMatchArray(['windows' => 1, 'android' => 0, 'ps4' => 0, 'ps5' => 0])
        // Its own facet keeps the alternatives.
        ->and(facetCounts('activation_services', ['activation_service' => 'steam']))
        ->toMatchArray(['steam' => 1, 'playstation-store' => 2])
        // With PS5 chosen, PS4 still counts, and other facets follow PS5.
        ->and(facetCounts('platforms', ['platform' => 'ps5']))->toMatchArray(['ps4' => 1, 'ps5' => 1])
        ->and(facetCounts('activation_services', ['platform' => 'ps5']))
        ->toMatchArray(['steam' => 0, 'playstation-store' => 1]);
});

it('rejects unknown filter values like the listing does', function () {
    $this->getJson(route('api.v1.catalog.filters', ['platform' => 'does-not-exist']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['platform.0' => 'La plataforma «does-not-exist» no existe.']);
});

it('runs the same number of queries whatever the catalog size', function () {
    $queries = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson(route('api.v1.catalog.filters', ['type' => 'digital_key']))->assertOk();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };

    Product::factory()->published()->create();
    $one = $queries();
    Product::factory()->published()->count(4)->create();

    expect($queries())->toBe($one);
});
