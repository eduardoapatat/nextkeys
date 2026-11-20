<?php

use App\Actions\Catalog\GenerateProductSlug;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\Title;
use Database\Seeders\DatabaseSeeder;

it('suggests the slug of every example product in the catalog doc', function () {
    $this->seed(DatabaseSeeder::class);
    $generator = new GenerateProductSlug;

    $products = Product::query()->with(['title', 'activationService', 'platforms', 'region'])->get();

    expect($products)->toHaveCount(11);

    foreach ($products as $product) {
        $suggestion = $generator->suggest($product->title, $product->type, $product->activationService, $product->platforms, $product->region);

        expect($suggestion)->toBe($product->slug);
    }
});

it('transliterates accents and drops symbols from the title name', function () {
    $title = Title::factory()->make(['name' => 'Pokémon: Edición Ñandú!']);

    expect((new GenerateProductSlug)->suggest($title, ProductType::Physical, null, [], null))
        ->toBe('pokemon-edicion-nandu-physical');
});

it('returns a free slug unchanged', function () {
    Product::factory()->create(['slug' => 'starfall-steam-windows-global-edition']);

    expect((new GenerateProductSlug)->unique('starfall-steam-windows-global'))
        ->toBe('starfall-steam-windows-global');
});

it('appends the first free numeric suffix to a taken slug', function () {
    Product::factory()->create(['slug' => 'starfall-steam-windows-global']);
    Product::factory()->create(['slug' => 'starfall-steam-windows-global-2']);
    Product::factory()->create(['slug' => 'starfall-steam-windows-global-4']);

    expect((new GenerateProductSlug)->unique('starfall-steam-windows-global'))
        ->toBe('starfall-steam-windows-global-3');
});
