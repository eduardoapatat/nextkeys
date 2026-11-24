<?php

use App\Actions\Orders\ExpireOrders;
use App\Actions\Orders\MarkOrderPaid;
use App\Actions\Orders\OrderCannotBePaid;
use App\Actions\Orders\OrderLineData;
use App\Actions\Orders\PlaceOrder;
use App\Enums\OrderStatus;
use App\Enums\ProductKeyStatus;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductKey;
use App\Models\User;

beforeEach(function () {
    $this->digital = Product::factory()->published()->create();
    $this->key = ProductKey::factory()->for($this->digital)->create();
    $this->placeDigitalOrder = fn (): Order => app(PlaceOrder::class)
        ->handle(User::factory()->create(), [new OrderLineData($this->digital->id, 1)]);
});

it('sells the keys and completes an order with nothing to ship', function () {
    $order = ($this->placeDigitalOrder)();

    app(MarkOrderPaid::class)->handle($order);

    expect($order->status)->toBe(OrderStatus::Completed)
        ->and($order->paid_at)->not->toBeNull()
        ->and($order->completed_at)->not->toBeNull()
        ->and($this->key->fresh()?->status)->toBe(ProductKeyStatus::Sold);
});

it('leaves a mixed order paid until it ships', function () {
    $box = Product::factory()->physical()->published()->create(['stock' => 2]);
    $customer = User::factory()->create();
    $order = app(PlaceOrder::class)->handle($customer, [
        new OrderLineData($this->digital->id, 1),
        new OrderLineData($box->id, 1),
    ], CustomerAddress::factory()->for($customer)->create());

    app(MarkOrderPaid::class)->handle($order);

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->completed_at)->toBeNull()
        ->and($this->key->fresh()?->status)->toBe(ProductKeyStatus::Sold)
        ->and($box->fresh()?->stock)->toBe(1);
});

it('ignores a repeated confirmation', function () {
    $order = ($this->placeDigitalOrder)();
    app(MarkOrderPaid::class)->handle($order);
    $paidAt = $order->paid_at;

    $this->travel(1)->minutes();
    app(MarkOrderPaid::class)->handle($order);

    expect($order->status)->toBe(OrderStatus::Completed)
        ->and($order->paid_at?->equalTo($paidAt))->toBeTrue();
});

it('takes a late payment when the stock is still there', function () {
    $order = ($this->placeDigitalOrder)();
    $this->travel(16)->minutes();
    app(ExpireOrders::class)->handle();

    app(MarkOrderPaid::class)->handle($order);

    expect($order->status)->toBe(OrderStatus::Completed)
        ->and($this->key->fresh()?->status)->toBe(ProductKeyStatus::Sold)
        ->and($this->key->fresh()?->order_item_id)->toBe($order->items()->sole()->id);
});

it('refuses a late payment when the stock is gone', function () {
    $order = ($this->placeDigitalOrder)();
    $this->travel(16)->minutes();
    app(ExpireOrders::class)->handle();
    $other = ($this->placeDigitalOrder)();

    expect(fn () => app(MarkOrderPaid::class)->handle($order))->toThrow(OrderCannotBePaid::class);

    expect($order->fresh()?->status)->toBe(OrderStatus::Expired)
        ->and($this->key->fresh()?->status)->toBe(ProductKeyStatus::Reserved)
        ->and($this->key->fresh()?->order_item_id)->toBe($other->items()->sole()->id);
});

it('refuses a payment for a cancelled order', function () {
    $order = Order::factory()->cancelled()->create();

    expect(fn () => app(MarkOrderPaid::class)->handle($order))->toThrow(OrderCannotBePaid::class);
    expect($order->fresh()?->status)->toBe(OrderStatus::Cancelled);
});
