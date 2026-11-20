<?php

use App\Models\Product;
use App\Models\ProductKey;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

it('stores the code encrypted and reads it back decrypted', function () {
    $key = ProductKey::factory()->create(['code' => 'ABCDE-12345-FGHIJ']);

    $storedCode = DB::table('product_keys')->where('id', $key->id)->value('code');

    expect($storedCode)->not->toContain('ABCDE-12345-FGHIJ')
        ->and($key->fresh()?->code)->toBe('ABCDE-12345-FGHIJ');
});

it('rejects a code that only differs in case or surrounding spaces', function () {
    $product = Product::factory()->create();
    ProductKey::factory()->for($product)->create(['code' => 'abcde-12345']);

    expect(fn () => ProductKey::factory()->for($product)->create(['code' => '  ABCDE-12345 ']))
        ->toThrow(UniqueConstraintViolationException::class, 'product_keys_code_hash_unique');
});

it('hides the code and its hash from serialization', function () {
    $key = ProductKey::factory()->create();

    expect($key->toArray())->not->toHaveKeys(['code', 'code_hash']);
});

it('refuses to hash a code when the hash secret is missing', function () {
    config(['catalog.key_hash_secret' => '']);

    expect(fn () => ProductKey::hashCode('ABCDE-12345'))
        ->toThrow(RuntimeException::class, 'KEY_HASH_SECRET is not set');
});
