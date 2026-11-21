<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Inserts a valid initialized attempt row for a new order. Returns its id.
 *
 * @param  array<string, mixed>  $overrides
 */
function insertPaymentAttemptRow(array $overrides = []): int
{
    $orderId = $overrides['order_id'] ?? insertOrderRow();
    $number = $overrides['number'] ?? 1;

    return DB::table('payment_attempts')->insertGetId([
        'order_id' => $orderId,
        'number' => $number,
        'gateway' => 'webpay_plus',
        'buy_order' => DB::table('orders')->where('id', $orderId)->value('reference')."-{$number}",
        'token' => webpayToken((string) random_int(0, PHP_INT_MAX)),
        'amount' => 19990,
        'status' => 'initialized',
        'created_at' => now(),
        'updated_at' => now(),
        ...$overrides,
    ]);
}

/**
 * The receipt columns of an authorized attempt.
 *
 * @return array<string, mixed>
 */
function authorizedAttemptColumns(): array
{
    return [
        'status' => 'authorized',
        'finished_at' => now(),
        'response_code' => 0,
        'authorization_code' => '1213',
        'transaction_date' => now(),
    ];
}

it('accepts a valid attempt', function () {
    insertPaymentAttemptRow();

    expect(DB::table('payment_attempts')->count())->toBe(1);
});

it('rejects an attempt number outside 1 to 5', function (int $number) {
    expect(fn () => insertPaymentAttemptRow(['number' => $number]))
        ->toThrow(QueryException::class, 'payment_attempts_number_range');
})->with([0, 6]);

it('rejects a second attempt with the same number for an order', function () {
    $orderId = insertOrderRow();
    insertPaymentAttemptRow(['order_id' => $orderId]);

    expect(fn () => insertPaymentAttemptRow(['order_id' => $orderId, 'buy_order' => 'NK-OTHER-1']))
        ->toThrow(QueryException::class, 'payment_attempts_order_id_number_unique');
});

it('rejects a repeated buy order or token', function (string $column) {
    $first = insertPaymentAttemptRow();
    $value = DB::table('payment_attempts')->where('id', $first)->value($column);

    expect(fn () => insertPaymentAttemptRow([$column => $value]))
        ->toThrow(QueryException::class, "payment_attempts_{$column}_unique");
})->with(['buy_order', 'token']);

it('rejects a token that is not 64 lowercase hex characters', function (string $token) {
    expect(fn () => insertPaymentAttemptRow(['token' => $token]))
        ->toThrow(QueryException::class, 'payment_attempts_token_format');
})->with([str_repeat('a', 63), str_repeat('A', 64), str_repeat('g', 64)]);

it('requires a token once the gateway answered', function (array $columns) {
    expect(fn () => insertPaymentAttemptRow(['token' => null, ...$columns]))
        ->toThrow(QueryException::class, 'payment_attempts_token_matches_status');
})->with([
    'rejected' => [['status' => 'rejected', 'finished_at' => now()]],
    'cancelled' => [['status' => 'cancelled', 'finished_at' => now()]],
]);

it('ties finished_at to leaving initialized', function (array $columns) {
    expect(fn () => insertPaymentAttemptRow($columns))
        ->toThrow(QueryException::class, 'payment_attempts_finished_at_matches_status');
})->with([
    'initialized but finished' => [['finished_at' => now()]],
    'aborted but not finished' => [['status' => 'aborted']],
]);

it('requires the receipt of an authorized attempt', function (string $missing) {
    expect(fn () => insertPaymentAttemptRow([...authorizedAttemptColumns(), $missing => null]))
        ->toThrow(QueryException::class, 'payment_attempts_receipt_matches_status');
})->with(['response_code', 'authorization_code', 'transaction_date']);

it('ties the refund columns to the refunded status', function (array $columns) {
    expect(fn () => insertPaymentAttemptRow($columns))
        ->toThrow(QueryException::class, 'payment_attempts_refund_matches_status');
})->with([
    'refunded without a refund' => [['status' => 'refunded', 'finished_at' => now()]],
    'authorized with a refund' => [[...authorizedAttemptColumns(), 'refund_type' => 'reversed', 'refunded_at' => now()]],
]);

it('lets an initialized attempt finish', function (array $columns) {
    $id = insertPaymentAttemptRow();

    DB::table('payment_attempts')->where('id', $id)->update($columns);

    expect(DB::table('payment_attempts')->where('id', $id)->value('status'))->toBe($columns['status']);
})->with([
    'authorized' => [authorizedAttemptColumns()],
    'rejected' => [['status' => 'rejected', 'finished_at' => now()]],
    'refunded' => [['status' => 'refunded', 'finished_at' => now(), 'refund_type' => 'reversed', 'refunded_at' => now()]],
]);

it('rejects any change of a finished attempt', function (string $to) {
    $id = insertPaymentAttemptRow(['status' => 'aborted', 'finished_at' => now()]);

    expect(fn () => DB::table('payment_attempts')->where('id', $id)->update(['status' => $to]))
        ->toThrow(QueryException::class, 'payment_attempts_status_transition');
})->with(['initialized', 'authorized', 'cancelled']);
