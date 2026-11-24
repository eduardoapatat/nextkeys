<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * An order row with one physical line and its shipment row, as the database
 * sees them. Returns the order id.
 *
 * @param  array<string, mixed>  $order
 * @param  array<string, mixed>  $shipment
 */
function insertShippedOrderRows(array $order = [], array $shipment = []): int
{
    $orderId = insertOrderRow(['total' => 19990 + 3990, 'shipping_cost' => 3990, ...$order]);
    $productId = insertProductRow(['type' => 'physical', 'stock' => 3, 'activation_service_id' => null, 'status' => 'published']);
    insertOrderItemRow($orderId, $productId, ['product_type' => 'physical']);
    insertShipmentRow($orderId, $shipment);

    return $orderId;
}

/**
 * @param  array<string, mixed>  $overrides
 */
function insertShipmentRow(int $orderId, array $overrides = []): void
{
    DB::table('shipments')->insert([
        'order_id' => $orderId,
        'recipient_name' => 'Ana Pérez',
        'phone' => '+56912345678',
        'region' => 'RM',
        'commune' => 'Ñuñoa',
        'street' => 'Av. Irarrázaval',
        'street_number' => '1234',
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
        ...$overrides,
    ]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function updateShipmentRow(int $orderId, array $attributes): void
{
    DB::table('shipments')->where('order_id', $orderId)->update($attributes);
}

function payOrderRow(int $orderId): void
{
    DB::table('orders')->where('id', $orderId)->update(['status' => 'paid', 'paid_at' => now()]);
}

/**
 * @return array<string, mixed>
 */
function shippedColumns(): array
{
    return ['status' => 'shipped', 'carrier' => 'starken', 'tracking_number' => '123456789', 'shipped_at' => now()];
}

it('accepts an order with physical lines and its shipment', function () {
    insertShippedOrderRows();

    checkDeferredConstraints();

    expect(DB::table('shipments')->count())->toBe(1);
});

it('rejects an order with physical lines and no shipment', function () {
    $orderId = insertOrderRow();
    insertOrderItemRow($orderId, insertProductRow(['type' => 'physical', 'stock' => 3, 'activation_service_id' => null]), ['product_type' => 'physical']);

    expect(fn () => checkDeferredConstraints())->toThrow(QueryException::class, 'orders_shipment_required');
});

it('rejects a shipment for an order without physical lines', function () {
    $orderId = insertOrderRow();
    insertOrderItemRow($orderId, insertProductRow());
    insertShipmentRow($orderId);

    expect(fn () => checkDeferredConstraints())->toThrow(QueryException::class, 'orders_shipment_required');
});

it('rejects a shipping fee on an order that does not ship', function () {
    $orderId = insertOrderRow(['shipping_cost' => 3990]);
    insertOrderItemRow($orderId, insertProductRow());

    expect(fn () => checkDeferredConstraints())->toThrow(QueryException::class, 'orders_shipment_required');
});

it('rejects a negative shipping fee', function () {
    expect(fn () => insertOrderRow(['shipping_cost' => -1]))
        ->toThrow(QueryException::class, 'orders_shipping_cost_not_negative');
});

it('rejects a second shipment for the same order', function () {
    $orderId = insertShippedOrderRows();

    expect(fn () => insertShipmentRow($orderId))->toThrow(QueryException::class, 'shipments_order_id_unique');
});

it('rejects an unknown status, region or carrier', function (array $overrides, string $constraint) {
    expect(fn () => insertShippedOrderRows(shipment: $overrides))->toThrow(QueryException::class, $constraint);
})->with([
    'status' => [['status' => 'lost'], 'shipments_status_check'],
    'region' => [['region' => 'XX'], 'shipments_region_check'],
    'carrier' => [[...shippedColumns(), 'carrier' => 'dhl'], 'shipments_carrier_check'],
]);

it('ties the carrier, tracking number and dates to the status', function (array $overrides, string $constraint) {
    expect(fn () => insertShippedOrderRows(shipment: $overrides))->toThrow(QueryException::class, $constraint);
})->with([
    'shipped without carrier' => [[...shippedColumns(), 'carrier' => null], 'shipments_shipped_matches_status'],
    'shipped without tracking number' => [[...shippedColumns(), 'tracking_number' => null], 'shipments_shipped_matches_status'],
    'pending with a carrier' => [['carrier' => 'starken'], 'shipments_shipped_matches_status'],
    'blank tracking number' => [[...shippedColumns(), 'tracking_number' => ' '], 'shipments_tracking_number_not_blank'],
    'delivered without date' => [[...shippedColumns(), 'status' => 'delivered'], 'shipments_delivered_at_matches_status'],
    'shipped with a delivery date' => [[...shippedColumns(), 'delivered_at' => now()], 'shipments_delivered_at_matches_status'],
]);

it('ships a paid order and delivers it', function () {
    $orderId = insertShippedOrderRows();
    payOrderRow($orderId);

    updateShipmentRow($orderId, shippedColumns());
    updateShipmentRow($orderId, ['status' => 'delivered', 'delivered_at' => now()]);

    expect(DB::table('shipments')->value('status'))->toBe('delivered');
});

it('ships only a paid order', function (string $status) {
    $orderId = insertShippedOrderRows(['status' => $status, 'cancelled_at' => $status === 'cancelled' ? now() : null]);

    expect(fn () => updateShipmentRow($orderId, shippedColumns()))
        ->toThrow(QueryException::class, 'shipments_status_transition');
})->with(['pending', 'cancelled', 'expired']);

it('rejects any other shipment transition', function (array $steps, array $to) {
    $orderId = insertShippedOrderRows();
    payOrderRow($orderId);

    foreach ($steps as $step) {
        updateShipmentRow($orderId, $step);
    }

    expect(fn () => updateShipmentRow($orderId, $to))->toThrow(QueryException::class, 'shipments_status_transition');
})->with([
    'pending to delivered' => [[], [...shippedColumns(), 'status' => 'delivered', 'delivered_at' => now()]],
    'shipped to pending' => [[shippedColumns()], ['status' => 'pending', 'carrier' => null, 'tracking_number' => null, 'shipped_at' => null]],
    'delivered to shipped' => [[shippedColumns(), ['status' => 'delivered', 'delivered_at' => now()]], ['status' => 'shipped', 'delivered_at' => null]],
]);

it('completes an order only once its shipment is delivered', function () {
    $orderId = insertShippedOrderRows();
    payOrderRow($orderId);
    $complete = fn () => DB::transaction(fn () => DB::table('orders')->where('id', $orderId)->update(['status' => 'completed', 'completed_at' => now()]));

    expect($complete)->toThrow(QueryException::class, 'orders_status_transition');

    updateShipmentRow($orderId, shippedColumns());
    expect($complete)->toThrow(QueryException::class, 'orders_status_transition');

    updateShipmentRow($orderId, ['status' => 'delivered', 'delivered_at' => now()]);
    $complete();
    expect(DB::table('orders')->where('id', $orderId)->value('status'))->toBe('completed');
});
