<?php

use App\Actions\Catalog\DeleteProduct;
use App\Models\Product;
use App\Models\ProductKey;

it('deletes a product without keys and its platforms', function () {
    $product = Product::factory()->create();

    app(DeleteProduct::class)->handle($product);

    $this->assertModelMissing($product);
    $this->assertDatabaseMissing('product_platforms', ['product_id' => $product->id]);
});

it('refuses to delete a product that has keys', function () {
    $product = Product::factory()->create();
    ProductKey::factory()->for($product)->create();

    $errors = validationErrorsOf(fn () => app(DeleteProduct::class)->handle($product));

    expect($errors['product'])->toBe(['Este producto tiene claves y no se puede eliminar. Despublícalo en su lugar.']);
    $this->assertModelExists($product);
});
