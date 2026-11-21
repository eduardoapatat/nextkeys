<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Updates a key row straight in the database, bypassing the Actions.
 *
 * @param  array<string, mixed>  $values
 */
function updateProductKeyRow(int $keyId, array $values): void
{
    DB::table('product_keys')->where('id', $keyId)->update($values);
}

beforeEach(function () {
    $this->productId = insertProductRow();
    $this->orderItemId = insertOrderItemRow(insertOrderRow(), $this->productId, [
        'quantity' => 2,
        'line_total' => 39980,
    ]);
});

it('rejects a reserved or sold key without an order line', function (string $status) {
    expect(fn () => insertProductKeyRow($this->productId, ['status' => $status]))
        ->toThrow(QueryException::class, 'product_keys_order_item_matches_status');
})->with(['reserved', 'sold']);

it('rejects an available key linked to an order line', function () {
    expect(fn () => insertProductKeyRow($this->productId, ['order_item_id' => $this->orderItemId]))
        ->toThrow(QueryException::class, 'product_keys_order_item_matches_status');
});

it('rejects a key linked to a line of another product', function () {
    expect(fn () => insertProductKeyRow(insertProductRow(), [
        'status' => 'reserved',
        'order_item_id' => $this->orderItemId,
    ]))->toThrow(QueryException::class, 'product_keys_order_item_id_product_id_foreign');
});

it('rejects more keys on a line than its quantity', function () {
    foreach (range(1, 3) as $ignored) {
        insertProductKeyRow($this->productId);
    }

    expect(fn () => DB::table('product_keys')->where('product_id', $this->productId)->update([
        'status' => 'reserved',
        'order_item_id' => $this->orderItemId,
    ]))->toThrow(QueryException::class, 'product_keys_order_item_quantity');
});

it('allows the transitions of the key lifecycle', function () {
    $keyId = insertProductKeyRow($this->productId);

    updateProductKeyRow($keyId, ['status' => 'reserved', 'order_item_id' => $this->orderItemId]);
    updateProductKeyRow($keyId, ['status' => 'available', 'order_item_id' => null]);
    updateProductKeyRow($keyId, ['status' => 'reserved', 'order_item_id' => $this->orderItemId]);
    updateProductKeyRow($keyId, ['status' => 'sold']);

    expect(DB::table('product_keys')->where('id', $keyId)->value('status'))->toBe('sold');
});

it('rejects a sold key going back', function (array $values) {
    $keyId = insertProductKeyRow($this->productId, ['status' => 'reserved', 'order_item_id' => $this->orderItemId]);
    updateProductKeyRow($keyId, ['status' => 'sold']);

    expect(fn () => updateProductKeyRow($keyId, $values))
        ->toThrow(QueryException::class, 'product_keys_status_transition');
})->with([
    'to available' => [['status' => 'available', 'order_item_id' => null]],
    'to reserved' => [['status' => 'reserved']],
]);

it('rejects selling an available key without reserving it', function () {
    $keyId = insertProductKeyRow($this->productId);

    expect(fn () => updateProductKeyRow($keyId, ['status' => 'sold', 'order_item_id' => $this->orderItemId]))
        ->toThrow(QueryException::class, 'product_keys_status_transition');
});

it('rejects moving a key to another order without releasing it', function () {
    $keyId = insertProductKeyRow($this->productId, ['status' => 'reserved', 'order_item_id' => $this->orderItemId]);
    $otherItemId = insertOrderItemRow(insertOrderRow(), $this->productId);

    expect(fn () => updateProductKeyRow($keyId, ['order_item_id' => $otherItemId]))
        ->toThrow(QueryException::class, 'product_keys_status_transition');
});

it('rejects a reveal on a key that is not sold', function () {
    $available = insertProductKeyRow($this->productId);

    expect(fn () => updateProductKeyRow($available, ['revealed_at' => now()]))
        ->toThrow(QueryException::class, 'product_keys_revealed_only_when_sold');
});

it('records a reveal on a sold key', function () {
    $sold = insertProductKeyRow($this->productId, ['status' => 'sold', 'order_item_id' => $this->orderItemId]);

    updateProductKeyRow($sold, ['revealed_at' => now()]);

    expect(DB::table('product_keys')->where('id', $sold)->value('revealed_at'))->not->toBeNull();
});
