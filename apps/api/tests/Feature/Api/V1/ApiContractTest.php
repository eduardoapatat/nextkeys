<?php

use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/*
 * The storefront (apps/web) is tested against the fixtures in
 * contracts/api/v1. They come from these real responses: run with
 * CONTRACTS_UPDATE=1 to rewrite them after changing an API Resource.
 */

/**
 * Reduces a decoded JSON value to its shape: objects keep their keys, lists
 * merge the shapes of their items, and scalars and null become leaves.
 */
function contractShape(mixed $value): mixed
{
    if (! is_array($value) || $value === []) {
        return null;
    }

    if (array_is_list($value)) {
        return ['[]' => array_reduce($value, fn (mixed $carry, mixed $item): mixed => mergeContractShapes($carry, contractShape($item)))];
    }

    $shape = [];
    foreach ($value as $key => $item) {
        $shape[$key] = contractShape($item);
    }
    ksort($shape);

    return $shape;
}

/**
 * A leaf merged with an object keeps the object, so a nullable object
 * (`region`, `activation_service`) has the same shape in every item.
 */
function mergeContractShapes(mixed $a, mixed $b): mixed
{
    if (! is_array($a)) {
        return $b;
    }

    if (! is_array($b)) {
        return $a;
    }

    foreach ($b as $key => $shape) {
        $a[$key] = mergeContractShapes($a[$key] ?? null, $shape);
    }
    ksort($a);

    return $a;
}

beforeEach(function () {
    config([
        'app.debug' => false,
        'filesystems.disks.public.url' => 'http://localhost:8000/storage',
    ]);
    Storage::forgetDisk('public');
    URL::forceRootUrl('http://localhost:8000');

    $this->seed(DatabaseSeeder::class);
    Product::query()->where('slug', 'starfall-steam-macos-windows-global')->update(['cover_image' => 'covers/starfall.jpg']);
});

it('answers with the shape of its contract fixture', function (string $fixture, string $uri, int $status) {
    $response = $this->getJson($uri)->assertStatus($status);
    $path = base_path("../../contracts/api/v1/{$fixture}.json");

    if (env('CONTRACTS_UPDATE')) {
        file_put_contents($path, json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
    }

    expect($path)->toBeFile("Missing fixture: run CONTRACTS_UPDATE=1 php artisan test --filter=ApiContract to write {$fixture}.json.");

    $expected = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

    expect(contractShape($response->json()))->toBe(
        contractShape($expected),
        "The /api/v1 response no longer matches contracts/api/v1/{$fixture}.json. If the change is intended, regenerate the fixtures with CONTRACTS_UPDATE=1 php artisan test --filter=ApiContract and update the valibot schemas in apps/web/app/api/schemas.ts.",
    );
})->with([
    'products.index' => ['products.index', '/api/v1/products', 200],
    'products.index.422' => ['products.index.422', '/api/v1/products?platform=does-not-exist', 422],
    'products.show' => ['products.show', '/api/v1/products/starfall-steam-macos-windows-global', 200],
    'products.show.404' => ['products.show.404', '/api/v1/products/does-not-exist', 404],
    'catalog.filters' => ['catalog.filters', '/api/v1/catalog/filters', 200],
]);
