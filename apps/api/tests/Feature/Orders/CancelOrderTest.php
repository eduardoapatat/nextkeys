<?php

use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\OrderLineData;
use App\Actions\Orders\PlaceOrder;
use App\Enums\OrderStatus;
use App\Enums\ProductKeyStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductKey;
use App\Models\User;

it('cancels a pending order and releases its keys and boxes', function () {
    $digital = Product::factory()->published()->create();
    $key = ProductKey::factory()->for($digital)->create();
    $box = Product::factory()->physical()->published()->create(['stock' => 3]);
    $order = app(PlaceOrder::class)->handle(User::factory()->create(), [
        new OrderLineData($digital->id, 1),
        new OrderLineData($box->id, 2),
    ]);

    app(CancelOrder::class)->handle($order);

    expect($order->status)->toBe(OrderStatus::Cancelled)
        ->and($order->cancelled_at)->not->toBeNull()
        ->and($key->fresh()?->status)->toBe(ProductKeyStatus::Available)
        ->and($key->fresh()?->order_item_id)->toBeNull()
        ->and($box->fresh()?->stock)->toBe(3);
});

it('refuses to cancel an order that is not pending', function (Order $order) {

    $errors = validationErrorsOf(fn () => app(CancelOrder::class)->handle($order));

    expect($errors)->toBe(['order' => ['Solo se puede cancelar un pedido pendiente.']]);
})->with([
    'paid' => fn () => Order::factory()->paid()->create(),
    'expired' => fn () => Order::factory()->expired()->create(),
]);
