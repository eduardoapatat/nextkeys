<?php

use App\Models\Product;
use App\Models\ProductKey;

it('counts only available keys as stock of a key-delivered product', function () {
    $product = Product::factory()->create();
    ProductKey::factory()->for($product)->count(2)->create();
    ProductKey::factory()->for($product)->reserved()->create();
    ProductKey::factory()->for($product)->sold()->create();

    expect($product->availableStock())->toBe(2);
});

it('uses the stock column as stock of a physical product', function () {
    $product = Product::factory()->physical()->create(['stock' => 7]);

    expect($product->availableStock())->toBe(7);
});
