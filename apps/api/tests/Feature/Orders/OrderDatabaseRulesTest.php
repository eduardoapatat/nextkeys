<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Moves an order row straight to a status, bypassing the Actions, setting the
 * timestamp each status requires.
 */
function updateOrderStatusRow(int $orderId, string $status): void
{
    DB::table('orders')->where('id', $orderId)->update([
        'status' => $status,
        'paid_at' => in_array($status, ['paid', 'completed'], true) ? now() : null,
        'completed_at' => $status === 'completed' ? now() : null,
        'cancelled_at' => $status === 'cancelled' ? now() : null,
    ]);
}

it('rejects an unknown order status', function () {
    expect(fn () => insertOrderRow(['status' => 'refunded']))
        ->toThrow(QueryException::class, 'orders_status_check');
});

it('rejects a total of zero', function () {
    expect(fn () => insertOrderRow(['total' => 0]))
        ->toThrow(QueryException::class, 'orders_total_positive');
});

it('rejects a malformed reference', function (string $reference) {
    expect(fn () => insertOrderRow(['reference' => $reference]))
        ->toThrow(QueryException::class, 'orders_reference_format');
})->with(['lowercase' => 'NK-7qf2k9', 'ambiguous letter' => 'NK-7QF2KI', 'too short' => 'NK-7QF2K', 'no prefix' => 'XX-7QF2K9']);

it('rejects a duplicate reference', function () {
    insertOrderRow(['reference' => 'NK-7QF2K9']);

    expect(fn () => insertOrderRow(['reference' => 'NK-7QF2K9']))
        ->toThrow(QueryException::class, 'orders_reference_unique');
});

it('rejects timestamps that do not match the status', function (array $row, string $constraint) {
    expect(fn () => insertOrderRow($row))->toThrow(QueryException::class, $constraint);
})->with([
    'paid without paid_at' => [['status' => 'paid'], 'orders_paid_at_matches_status'],
    'pending with paid_at' => [['paid_at' => '2026-11-20 10:00'], 'orders_paid_at_matches_status'],
    'completed without completed_at' => [['status' => 'completed', 'paid_at' => '2026-11-20 10:00'], 'orders_completed_at_matches_status'],
    'cancelled without cancelled_at' => [['status' => 'cancelled'], 'orders_cancelled_at_matches_status'],
]);

it('allows the transitions of the order lifecycle', function (string $from, string $to) {
    $orderId = insertOrderRow();

    if ($from !== 'pending') {
        updateOrderStatusRow($orderId, $from);
    }
    updateOrderStatusRow($orderId, $to);

    expect(DB::table('orders')->where('id', $orderId)->value('status'))->toBe($to);
})->with([
    ['pending', 'paid'],
    ['pending', 'cancelled'],
    ['pending', 'expired'],
    ['expired', 'pending'],
    ['paid', 'completed'],
]);

it('rejects any other transition', function (string $from, string $to) {
    $orderId = insertOrderRow();
    foreach (match ($from) {
        'paid' => ['paid'],
        'completed' => ['paid', 'completed'],
        default => [$from],
    } as $step) {
        updateOrderStatusRow($orderId, $step);
    }

    expect(fn () => updateOrderStatusRow($orderId, $to))
        ->toThrow(QueryException::class, 'orders_status_transition');
})->with([
    ['paid', 'pending'],
    ['paid', 'cancelled'],
    ['completed', 'paid'],
    ['cancelled', 'pending'],
    ['cancelled', 'paid'],
    ['expired', 'paid'],
    ['expired', 'cancelled'],
]);

it('rejects an order line quantity outside 1 to 5', function (int $quantity) {
    $orderId = insertOrderRow();

    expect(fn () => insertOrderItemRow($orderId, insertProductRow(), [
        'quantity' => $quantity,
        'line_total' => 19990 * $quantity,
    ]))->toThrow(QueryException::class, 'order_items_quantity_range');
})->with([0, 6]);

it('rejects an order line total that is not price times quantity', function () {
    expect(fn () => insertOrderItemRow(insertOrderRow(), insertProductRow(), ['quantity' => 2, 'line_total' => 19990]))
        ->toThrow(QueryException::class, 'order_items_line_total_matches');
});

it('rejects the same product twice in an order', function () {
    $orderId = insertOrderRow();
    $productId = insertProductRow();
    insertOrderItemRow($orderId, $productId);

    expect(fn () => insertOrderItemRow($orderId, $productId))
        ->toThrow(QueryException::class, 'order_items_order_id_product_id_unique');
});

it('rejects deleting a product that has orders', function () {
    $productId = insertProductRow();
    insertOrderItemRow(insertOrderRow(), $productId);

    expect(fn () => DB::table('products')->where('id', $productId)->delete())
        ->toThrow(QueryException::class, 'order_items_product_id_foreign');
});

it('rejects more resends of the payment confirmation than allowed', function (int $resends) {
    expect(fn () => insertOrderRow(['paid_email_resends' => $resends]))
        ->toThrow(QueryException::class, 'orders_paid_email_resends_check');
})->with([4, -1]);
