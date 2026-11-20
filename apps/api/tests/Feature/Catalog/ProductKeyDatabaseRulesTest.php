<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Inserts a key row straight into the database, bypassing the model.
 *
 * @param  array<string, mixed>  $overrides
 */
function insertProductKeyRow(int $productId, array $overrides = []): void
{
    DB::table('product_keys')->insert([
        'product_id' => $productId,
        'product_type' => 'digital_key',
        'code' => 'not-really-encrypted',
        'code_hash' => hash('sha256', fake()->unique()->uuid()),
        'status' => 'available',
        'created_at' => now(),
        'updated_at' => now(),
        ...$overrides,
    ]);
}

it('rejects a key whose product type is physical', function () {
    $productId = insertProductRow(['type' => 'physical', 'activation_service_id' => null, 'stock' => 5]);

    expect(fn () => insertProductKeyRow($productId, ['product_type' => 'physical']))
        ->toThrow(QueryException::class, 'product_keys_product_type_check');
});

it('rejects a key that claims a type different from its product', function () {
    $productId = insertProductRow(['type' => 'physical', 'activation_service_id' => null, 'stock' => 5]);

    expect(fn () => insertProductKeyRow($productId, ['product_type' => 'digital_key']))
        ->toThrow(QueryException::class, 'product_keys_product_id_product_type_foreign');
});

it('rejects the same code hash in two different products', function () {
    $hash = hash('sha256', 'same-code');
    insertProductKeyRow(insertProductRow(), ['code_hash' => $hash]);

    expect(fn () => insertProductKeyRow(insertProductRow(), ['code_hash' => $hash]))
        ->toThrow(QueryException::class, 'product_keys_code_hash_unique');
});

it('rejects an unknown key status', function () {
    expect(fn () => insertProductKeyRow(insertProductRow(), ['status' => 'refunded']))
        ->toThrow(QueryException::class, 'product_keys_status_check');
});

it('rejects deleting a product that has keys', function () {
    $productId = insertProductRow();
    insertProductKeyRow($productId);

    expect(fn () => DB::table('products')->where('id', $productId)->delete())
        ->toThrow(QueryException::class, 'product_keys_product_id_product_type_foreign');
});
