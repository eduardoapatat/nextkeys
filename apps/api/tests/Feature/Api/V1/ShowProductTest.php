<?php

use App\Models\Product;
use App\Models\Title;
use Illuminate\Support\Facades\Storage;

it('shows a published product with its title and the shape the storefront expects', function () {
    $product = Product::factory()->published()->create();

    $this->getJson(route('api.v1.products.show', $product->slug))
        ->assertOk()
        ->assertExactJsonStructure(['data' => [
            'slug', 'type' => ['slug', 'name'], 'price', 'in_stock', 'cover_url',
            'title' => ['name', 'description'],
            'platforms' => ['*' => ['slug', 'name']],
            'region' => ['slug', 'name'],
            'activation_service' => ['slug', 'name'],
            'variants' => ['*' => [
                'slug', 'type' => ['slug', 'name'], 'price', 'in_stock', 'cover_url',
                'title' => ['name'],
                'platforms' => ['*' => ['slug', 'name']],
                'region' => ['slug', 'name'],
                'activation_service' => ['slug', 'name'],
            ]],
        ]])
        ->assertJsonPath('data.slug', $product->slug)
        ->assertJsonPath('data.title.name', $product->title->name)
        ->assertJsonPath('data.title.description', $product->title->description);
});

it('lists every published variant of the title, the current one included, cheapest first', function () {
    $title = Title::factory()->create();
    $current = Product::factory()->published()->for($title)->create(['price' => 20000]);
    $cheaperSibling = Product::factory()->published()->for($title)->create(['price' => 10000]);
    Product::factory()->for($title)->create(['price' => 5000]);
    Product::factory()->archived()->for($title)->create(['price' => 5000]);
    Product::factory()->published()->create(['price' => 5000]);

    $this->getJson(route('api.v1.products.show', $current->slug))
        ->assertOk()
        ->assertJsonPath('data.variants.*.slug', [$cheaperSibling->slug, $current->slug]);
});

it('returns null for the missing references of a physical product', function () {
    $product = Product::factory()->physical()->published()->create(['region_id' => null]);

    $this->getJson(route('api.v1.products.show', $product->slug))
        ->assertOk()
        ->assertJsonPath('data.activation_service', null)
        ->assertJsonPath('data.region', null)
        ->assertJsonPath('data.cover_url', null);
});

it('returns the public URL of the cover', function () {
    Storage::fake('public');
    $product = Product::factory()->published()->create(['cover_image' => 'covers/starfall.jpg']);

    $this->getJson(route('api.v1.products.show', $product->slug))
        ->assertOk()
        ->assertJsonPath('data.cover_url', Storage::disk('public')->url('covers/starfall.jpg'));
});

it('hides products that are not published', function (string $slug) {
    $this->getJson(route('api.v1.products.show', $slug))->assertNotFound();
})->with([
    'draft' => [fn () => Product::factory()->create()->slug],
    'archived' => [fn () => Product::factory()->archived()->create()->slug],
    'unknown slug' => ['does-not-exist'],
]);
