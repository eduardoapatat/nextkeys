<?php

use App\Actions\Orders\MarkOrderDelivered;
use App\Actions\Orders\MarkOrderPaid;
use App\Actions\Orders\OrderLineData;
use App\Actions\Orders\PlaceOrder;
use App\Actions\Orders\ShipOrder;
use App\Enums\Carrier;
use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Jobs\SendOrderShippedEmail;
use App\Mail\OrderShippedMail;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductKey;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->customer = User::factory()->create();
    $this->box = Product::factory()->physical()->published()->create(['stock' => 3]);
    $this->order = app(PlaceOrder::class)->handle(
        $this->customer,
        [new OrderLineData($this->box->id, 1)],
        CustomerAddress::factory()->for($this->customer)->create(),
    );
    $this->pay = fn () => app(MarkOrderPaid::class)->handle($this->order);
});

it('ships a paid order and queues the shipping email once', function () {
    Queue::fake();
    ($this->pay)();

    app(ShipOrder::class)->handle($this->order, Carrier::Chilexpress, ' 99887766 ');

    $shipment = $this->order->shipment()->sole();
    expect($shipment->status)->toBe(ShipmentStatus::Shipped)
        ->and($shipment->carrier)->toBe(Carrier::Chilexpress)
        ->and($shipment->tracking_number)->toBe('99887766')
        ->and($shipment->shipped_at)->not->toBeNull()
        ->and($this->order->status)->toBe(OrderStatus::Paid);
    Queue::assertPushed(SendOrderShippedEmail::class, fn (SendOrderShippedEmail $job): bool => $job->orderId === $this->order->id);

    expect(validationErrorsOf(fn () => app(ShipOrder::class)->handle($this->order, Carrier::Starken, '123456')))
        ->toBe(['order' => ['Este pedido ya fue despachado.']]);
    Queue::assertPushed(SendOrderShippedEmail::class, 1);
});

it('ships only a paid order with physical lines', function () {
    Queue::fake();

    expect(validationErrorsOf(fn () => app(ShipOrder::class)->handle($this->order, Carrier::Starken, '123456')))
        ->toBe(['order' => ['Solo se despacha un pedido pagado.']]);

    $digital = Product::factory()->published()->create();
    ProductKey::factory()->for($digital)->create();
    $digitalOrder = app(PlaceOrder::class)->handle(User::factory()->create(), [new OrderLineData($digital->id, 1)]);
    app(MarkOrderPaid::class)->handle($digitalOrder);

    expect(validationErrorsOf(fn () => app(ShipOrder::class)->handle($digitalOrder, Carrier::Starken, '123456')))
        ->toBe(['order' => ['Este pedido no tiene productos físicos que despachar.']]);
    Queue::assertNotPushed(SendOrderShippedEmail::class);
});

it('sends nothing when shipping rolls back', function () {
    Mail::fake();
    ($this->pay)();

    expect(fn () => DB::transaction(function (): void {
        app(ShipOrder::class)->handle($this->order, Carrier::Starken, '123456');

        throw new RuntimeException('The outer transaction fails after shipping.');
    }))->toThrow(RuntimeException::class);

    Mail::assertNotSent(OrderShippedMail::class);
});

it('completes the order when the shipment is delivered', function () {
    Queue::fake();
    ($this->pay)();
    app(ShipOrder::class)->handle($this->order, Carrier::Starken, '123456');

    app(MarkOrderDelivered::class)->handle($this->order);

    $shipment = $this->order->shipment()->sole();
    expect($this->order->status)->toBe(OrderStatus::Completed)
        ->and($this->order->completed_at)->not->toBeNull()
        ->and($shipment->status)->toBe(ShipmentStatus::Delivered)
        ->and($shipment->returnableUntil()?->toDateString())->toBe(now()->addDays(10)->toDateString());
});

it('marks as delivered only a shipped order', function () {
    ($this->pay)();

    expect(validationErrorsOf(fn () => app(MarkOrderDelivered::class)->handle($this->order)))
        ->toBe(['order' => ['Solo se marca como entregado un pedido despachado.']]);
    expect(Order::query()->whereKey($this->order->id)->value('status'))->toBe(OrderStatus::Paid);
});
