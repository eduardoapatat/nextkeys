<?php

use App\Enums\ProductType;
use App\Models\ActivationService;
use App\Models\Platform;
use App\Models\Product;
use App\Models\ProductKey;
use App\Models\Region;
use App\Models\Title;
use Illuminate\Support\Facades\DB;

/**
 * Slugs of the listed products, in the order the API returns them.
 *
 * @param  array<string, string>  $query
 * @return list<string>
 */
function listedSlugs(array $query = []): array
{
    /** @var list<string> */
    return test()->getJson(route('api.v1.products.index', $query))
        ->assertOk()
        ->json('data.*.slug');
}

it('lists only published products', function () {
    $published = Product::factory()->published()->create();
    Product::factory()->create();
    Product::factory()->archived()->create();

    expect(listedSlugs())->toBe([$published->slug]);
});

it('exposes only the storefront fields of each product', function () {
    $product = Product::factory()->published()->create();

    $item = $this->getJson(route('api.v1.products.index'))->assertOk()->json('data.0');

    expect(array_keys($item))->toBe([
        'slug', 'type', 'price', 'in_stock', 'cover_url', 'cover_band', 'title', 'platforms', 'region', 'activation_service',
    ])
        ->and($item['type'])->toBe(['slug' => $product->type->value, 'name' => $product->type->label()])
        ->and($item['price'])->toBe($product->price)
        ->and($item['title'])->toBe(['name' => $product->title->name])
        ->and($item['region'])->toBe(['slug' => $product->region?->slug, 'name' => $product->region?->name])
        ->and($item['platforms'])->toBe($product->platforms->map(fn (Platform $platform) => [
            'slug' => $platform->slug,
            'name' => $platform->name,
        ])->all());
});

it('reports whether each product can be bought now', function (Product $product, bool $inStock) {
    $this->getJson(route('api.v1.products.index'))
        ->assertOk()
        ->assertJsonPath('data.0.slug', $product->slug)
        ->assertJsonPath('data.0.in_stock', $inStock);
})->with([
    'digital key with an available code' => [fn () => tap(Product::factory()->published()->create(), function (Product $product) {
        ProductKey::factory()->for($product)->create();
    }), true],
    'digital key with only reserved and sold codes' => [fn () => tap(Product::factory()->published()->create(), function (Product $product) {
        ProductKey::factory()->for($product)->reserved()->create();
        ProductKey::factory()->for($product)->sold()->create();
    }), false],
    'license with an available code' => [fn () => tap(Product::factory()->license()->published()->create(), function (Product $product) {
        ProductKey::factory()->for($product)->create();
    }), true],
    'license with no codes' => [fn () => Product::factory()->license()->published()->create(), false],
    'physical with stock' => [fn () => Product::factory()->physical()->published()->create(['stock' => 3]), true],
    'physical with no stock' => [fn () => Product::factory()->physical()->published()->create(['stock' => 0]), false],
]);

it('filters by platform, matching products that cover any of the given platforms', function () {
    $ps4 = Platform::factory()->create(['slug' => 'ps4']);
    $ps5 = Platform::factory()->create(['slug' => 'ps5']);
    $switch = Platform::factory()->create(['slug' => 'switch']);
    $ps5Only = Product::factory()->published()->withPlatforms($ps5)->create(['price' => 10000]);
    $ps4AndPs5 = Product::factory()->published()->withPlatforms($ps4, $ps5)->create(['price' => 20000]);
    $switchOnly = Product::factory()->published()->withPlatforms($switch)->create(['price' => 30000]);

    expect(listedSlugs(['platform' => 'ps4', 'sort' => 'price']))->toBe([$ps4AndPs5->slug])
        ->and(listedSlugs(['platform' => 'ps5', 'sort' => 'price']))->toBe([$ps5Only->slug, $ps4AndPs5->slug])
        ->and(listedSlugs(['platform' => 'ps4,switch', 'sort' => 'price']))->toBe([$ps4AndPs5->slug, $switchOnly->slug]);
});

it('filters by type, activation service and region, with several values each', function (string $filter, Closure $variant) {
    $first = Product::factory()->published()->create(['price' => 10000, ...$variant('first')]);
    $second = Product::factory()->published()->create(['price' => 20000, ...$variant('second')]);
    Product::factory()->published()->create($variant('third'));

    $slugOf = fn (Product $product): string => match ($filter) {
        'type' => $product->type->value,
        'activation_service' => (string) $product->activationService?->slug,
        'region' => (string) $product->region?->slug,
    };

    expect(listedSlugs([$filter => $slugOf($first), 'sort' => 'price']))->toBe([$first->slug])
        ->and(listedSlugs([$filter => $slugOf($first).','.$slugOf($second), 'sort' => 'price']))->toBe([$first->slug, $second->slug]);
})->with([
    'type' => ['type', fn (string $which) => [
        'type' => ['first' => ProductType::DigitalKey, 'second' => ProductType::License, 'third' => ProductType::Physical][$which],
        ...($which === 'third' ? ['activation_service_id' => null, 'stock' => 1] : []),
    ]],
    'activation service' => ['activation_service', fn (string $which) => [
        'activation_service_id' => ActivationService::factory()->create(['slug' => "service-{$which}"])->id,
    ]],
    'region' => ['region', fn (string $which) => [
        'region_id' => Region::factory()->create(['slug' => "region-{$which}"])->id,
    ]],
]);

it('combines different filters with AND', function () {
    $ps5 = Platform::factory()->create(['slug' => 'ps5']);
    $latam = Region::factory()->create(['slug' => 'latam']);
    $ps5InLatam = Product::factory()->published()->withPlatforms($ps5)->create(['region_id' => $latam->id]);
    Product::factory()->published()->withPlatforms($ps5)->create();
    Product::factory()->published()->create(['region_id' => $latam->id]);

    expect(listedSlugs(['platform' => 'ps5', 'region' => 'latam']))->toBe([$ps5InLatam->slug]);
});

it('sorts the listing', function (?string $sort, array $expectedTitles) {
    $make = fn (string $title, int $price, string $createdAt) => Product::factory()->published()
        ->for(Title::factory()->state(['name' => $title]))
        ->create(['price' => $price, 'created_at' => $createdAt]);
    $make('Bravo', 30000, '2026-11-18 10:00:00');
    $make('Charlie', 10000, '2026-11-20 10:00:00');
    $make('alpha', 20000, '2026-11-19 10:00:00');

    $titles = $this->getJson(route('api.v1.products.index', array_filter(['sort' => $sort])))
        ->assertOk()
        ->json('data.*.title.name');

    expect($titles)->toBe($expectedTitles);
})->with([
    'newest by default' => [null, ['Charlie', 'alpha', 'Bravo']],
    'newest' => ['newest', ['Charlie', 'alpha', 'Bravo']],
    'cheapest first' => ['price', ['Charlie', 'alpha', 'Bravo']],
    'most expensive first' => ['-price', ['Bravo', 'alpha', 'Charlie']],
    'title name ignoring case' => ['name', ['alpha', 'Bravo', 'Charlie']],
]);

it('breaks sorting ties by id so pages are stable', function (string $sort, bool $newestIdFirst) {
    $products = Product::factory()->published()->count(3)->create([
        'price' => 10000,
        'created_at' => '2026-11-20 10:00:00',
        'title_id' => Title::factory(),
    ]);
    $expected = $products->sortBy('id', descending: $newestIdFirst)->pluck('slug')->all();

    expect(listedSlugs(['sort' => $sort]))->toBe($expected);
})->with([
    'newest' => ['newest', true],
    'price' => ['price', false],
    '-price' => ['-price', false],
]);

it('paginates 24 products per page', function () {
    Product::factory()->published()->count(25)->create();

    $this->getJson(route('api.v1.products.index'))
        ->assertOk()
        ->assertJsonCount(24, 'data')
        ->assertJsonPath('meta.total', 25)
        ->assertJsonPath('meta.per_page', 24)
        ->assertJsonPath('meta.last_page', 2);

    $this->getJson(route('api.v1.products.index', ['page' => 2]))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('labels the previous and next page links in Spanish, as plain text', function () {
    Product::factory()->published()->create();

    $links = $this->getJson(route('api.v1.products.index'))->assertOk()->json('meta.links');

    expect(array_column($links, 'label'))->toBe(['« Anterior', '1', 'Siguiente »']);
});

it('rejects unknown filter values with the storefront message', function (string $filter, string $message) {
    $this->getJson(route('api.v1.products.index', [$filter => 'does-not-exist']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(["{$filter}.0" => $message]);
})->with([
    'platform' => ['platform', 'La plataforma «does-not-exist» no existe.'],
    'type' => ['type', 'El tipo «does-not-exist» no existe.'],
    'activation service' => ['activation_service', 'El servicio de activación «does-not-exist» no existe.'],
    'region' => ['region', 'La región «does-not-exist» no existe.'],
]);

it('rejects an unknown sort or an invalid page', function (array $query, string $field) {
    $this->getJson(route('api.v1.products.index', $query))
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor($field);
})->with([
    'unknown sort' => [['sort' => 'popular'], 'sort'],
    'page zero' => [['page' => '0'], 'page'],
    'page not a number' => [['page' => 'two'], 'page'],
]);

it('runs the same number of queries whatever the page size', function () {
    $queriesFor = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson(route('api.v1.products.index'))->assertOk();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };

    Product::factory()->published()->create();
    $withOneProduct = $queriesFor();
    Product::factory()->published()->count(4)->create();

    expect($queriesFor())->toBe($withOneProduct);
});

it('summarizes validation errors in Spanish, naming each parameter in Spanish', function (array $query, string $message) {
    $this->getJson(route('api.v1.products.index', $query))
        ->assertUnprocessable()
        ->assertJsonPath('message', $message);
})->with([
    'sort' => [['sort' => 'popular'], 'El orden seleccionado no es válido.'],
    'page' => [['page' => 'two'], 'El campo página debe ser un número entero.'],
    'one more error' => [['platform' => 'nope,nada'], 'La plataforma «nope» no existe. (y 1 error más)'],
    'several more errors' => [['platform' => 'nope,nada', 'sort' => 'popular'], 'El orden seleccionado no es válido. (y 2 errores más)'],
]);
