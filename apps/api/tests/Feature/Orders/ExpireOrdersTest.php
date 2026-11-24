<?php

use App\Actions\Orders\OrderLineData;
use App\Actions\Orders\PlaceOrder;
use App\Enums\OrderStatus;
use App\Enums\ProductKeyStatus;
use App\Models\CustomerAddress;
use App\Models\Product;
use App\Models\ProductKey;
use App\Models\User;

it('expires overdue pending orders and releases their stock', function () {
    $digital = Product::factory()->published()->create();
    $key = ProductKey::factory()->for($digital)->create();
    $box = Product::factory()->physical()->published()->create(['stock' => 2]);
    $customer = User::factory()->create();
    $overdue = app(PlaceOrder::class)->handle($customer, [
        new OrderLineData($digital->id, 1),
        new OrderLineData($box->id, 1),
    ], CustomerAddress::factory()->for($customer)->create());

    $this->travel(10)->minutes();
    $otherCustomer = User::factory()->create();
    $recent = app(PlaceOrder::class)->handle($otherCustomer, [new OrderLineData($box->id, 1)], CustomerAddress::factory()->for($otherCustomer)->create());
    $this->travel(6)->minutes();

    $this->artisan('orders:expire')->expectsOutput('Expired 1 order(s).')->assertSuccessful();

    expect($overdue->fresh()?->status)->toBe(OrderStatus::Expired)
        ->and($recent->fresh()?->status)->toBe(OrderStatus::Pending)
        ->and($key->fresh()?->status)->toBe(ProductKeyStatus::Available)
        ->and($key->fresh()?->order_item_id)->toBeNull()
        ->and($box->fresh()?->stock)->toBe(1);
});

it('is scheduled every minute', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('orders:expire');
});
