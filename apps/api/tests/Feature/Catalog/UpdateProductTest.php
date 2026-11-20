<?php

use App\Actions\Catalog\UpdateProduct;
use App\Enums\ProductType;
use App\Models\Platform;
use App\Models\Product;

/**
 * The product's current data with some fields changed.
 */
function dataOf(Product $product, mixed ...$overrides): mixed
{
    return productData(...[
        'titleId' => $product->title_id,
        'activationServiceId' => $product->activation_service_id,
        'regionId' => $product->region_id,
        'platformIds' => $product->platforms()->pluck('platforms.id')->all(),
        'price' => $product->price,
        'stock' => $product->stock,
        'isPublished' => $product->is_published,
        'slug' => null,
        ...$overrides,
    ]);
}

it('keeps the slug when the admin leaves it empty', function () {
    $product = Product::factory()->create(['slug' => 'starfall-pc']);

    app(UpdateProduct::class)->handle($product, dataOf($product, price: 24990, isPublished: true));
    checkDeferredConstraints();

    expect($product->refresh())
        ->slug->toBe('starfall-pc')
        ->price->toBe(24990)
        ->is_published->toBeTrue();
});

it('lets a product keep its own slug and identity', function () {
    $product = Product::factory()->create(['slug' => 'starfall-pc']);

    app(UpdateProduct::class)->handle($product, dataOf($product, slug: 'starfall-pc'));
    checkDeferredConstraints();

    expect($product->refresh()->slug)->toBe('starfall-pc');
});

it('replaces the platforms of a product', function () {
    $product = Product::factory()->create();
    $platforms = Platform::factory()->count(2)->create()->modelKeys();

    app(UpdateProduct::class)->handle($product, dataOf($product, platformIds: $platforms));
    checkDeferredConstraints();

    expect($product->platforms()->pluck('platforms.id')->sort()->values()->all())->toBe($platforms);
});

it('updates the stock of a physical product', function () {
    $product = Product::factory()->physical()->create(['stock' => 2]);

    app(UpdateProduct::class)->handle($product, dataOf($product, stock: 10));

    expect($product->refresh()->stock)->toBe(10);
});

it('rejects platforms that give the product the identity of another one', function () {
    $other = Product::factory()->create(['slug' => 'starfall-ps5-latam']);
    $product = Product::factory()->create([
        'title_id' => $other->title_id,
        'activation_service_id' => $other->activation_service_id,
        'region_id' => $other->region_id,
    ]);

    $errors = validationErrorsOf(fn () => app(UpdateProduct::class)->handle(
        $product,
        dataOf($product, platformIds: $other->platforms()->pluck('platforms.id')->all()),
    ));

    expect($errors['product'])->toBe(['Este producto ya existe: starfall-ps5-latam.']);
});

it('applies the rules of the product type', function () {
    $product = Product::factory()->create();

    $errors = validationErrorsOf(fn () => app(UpdateProduct::class)->handle($product, dataOf($product, stock: 5)));

    expect($errors)->toHaveKey('stock')
        ->and($product->refresh()->type)->toBe(ProductType::DigitalKey);
});
