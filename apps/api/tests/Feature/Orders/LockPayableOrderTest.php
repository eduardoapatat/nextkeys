<?php

use App\Actions\Orders\ExpireOrders;
use App\Actions\Orders\LockPayableOrder;
use App\Actions\Orders\MarkOrderPaid;
use App\Actions\Orders\OrderCannotBePaid;
use App\Actions\Orders\OrderCannotBePaidReason;
use App\Actions\Orders\OrderLineData;
use App\Actions\Orders\PlaceOrder;
use App\Enums\OrderStatus;
use App\Enums\ProductKeyStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductKey;
use App\Models\User;

beforeEach(function () {
    $this->digital = Product::factory()->published()->create();
    $this->key = ProductKey::factory()->for($this->digital)->create();
    $this->placeDigitalOrder = fn (): Order => app(PlaceOrder::class)
        ->handle(User::factory()->create(), [new OrderLineData($this->digital->id, 1)]);
    $this->expire = function (): void {
        $this->travel(16)->minutes();
        app(ExpireOrders::class)->handle();
    };
});

it('hands a pending order to the callback and returns its result', function () {
    $order = ($this->placeDigitalOrder)();

    $result = app(LockPayableOrder::class)->handle($order, fn (Order $locked): string => $locked->reference);

    expect($result)->toBe($order->reference);
});

it('reopens an expired order with a fresh hold when its stock is still there', function () {
    $order = ($this->placeDigitalOrder)();
    ($this->expire)();
    $this->freezeTime();

    $status = app(LockPayableOrder::class)->handle($order, fn (Order $locked): OrderStatus => $locked->status);

    $order->refresh();
    expect($status)->toBe(OrderStatus::Pending)
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->expires_at->toDateTimeString())->toBe(now()->addMinutes(15)->toDateTimeString())
        ->and($this->key->fresh()?->status)->toBe(ProductKeyStatus::Reserved)
        ->and($this->key->fresh()?->order_item_id)->toBe($order->items()->sole()->id);
});

it('refuses an order that cannot be charged without calling back', function (Closure $makeOrder, OrderCannotBePaidReason $reason) {
    $order = $makeOrder->call($this);
    $called = false;

    try {
        app(LockPayableOrder::class)->handle($order, function () use (&$called): void {
            $called = true;
        });
        $this->fail('The order was taken as payable.');
    } catch (OrderCannotBePaid $exception) {
        expect($exception->reason)->toBe($reason);
    }

    expect($called)->toBeFalse();
})->with([
    'paid' => [fn () => Order::factory()->completed()->create(), OrderCannotBePaidReason::AlreadyPaid],
    'cancelled' => [fn () => Order::factory()->cancelled()->create(), OrderCannotBePaidReason::Cancelled],
    'expired without stock' => [function () {
        $order = ($this->placeDigitalOrder)();
        ($this->expire)();
        ($this->placeDigitalOrder)();

        return $order;
    }, OrderCannotBePaidReason::OutOfStock],
]);

it('rolls back the reopening when the callback fails', function () {
    $order = ($this->placeDigitalOrder)();
    ($this->expire)();

    expect(fn () => app(LockPayableOrder::class)->handle($order, fn () => throw new RuntimeException('Rejected.')))
        ->toThrow(RuntimeException::class, 'Rejected.');

    expect($order->fresh()?->status)->toBe(OrderStatus::Expired)
        ->and($this->key->fresh()?->status)->toBe(ProductKeyStatus::Available);
});

it('records a late payment through the reopened order', function () {
    $order = ($this->placeDigitalOrder)();
    ($this->expire)();

    app(MarkOrderPaid::class)->handle($order);

    expect($order->status)->toBe(OrderStatus::Completed)
        ->and($this->key->fresh()?->status)->toBe(ProductKeyStatus::Sold);
});
