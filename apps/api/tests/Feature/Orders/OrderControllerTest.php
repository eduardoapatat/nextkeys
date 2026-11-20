<?php

use App\Actions\Orders\ExpireOrders;
use App\Actions\Orders\OrderLineData;
use App\Actions\Orders\PlaceOrder;
use App\Enums\OrderStatus;
use App\Enums\ProductKeyStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductKey;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->product = Product::factory()->published()->create(['price' => 19990]);
    $this->key = ProductKey::factory()->for($this->product)->create(['code' => 'SECRET-00001-ABCD']);
    $this->placeOrder = fn (?User $customer = null): Order => app(PlaceOrder::class)
        ->handle($customer ?? User::factory()->create(), [new OrderLineData($this->product->id, 1)]);
});

it('lists orders newest first', function () {
    $older = ($this->placeOrder)();
    $this->travel(1)->minutes();
    $newer = Order::factory()->completed()->create();

    $this->get(route('orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('orders/Index')
            ->where('orders.meta.total', 2)
            ->where('orders.data.0.reference', $newer->reference)
            ->where('orders.data.1.reference', $older->reference)
            ->where('orders.data.1.status', 'pending')
            ->where('orders.data.1.total', 19990));
});

it('filters orders by status', function () {
    ($this->placeOrder)();
    $completed = Order::factory()->completed()->create();

    $this->get(route('orders.index', ['filter' => ['status' => 'completed']]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('orders.meta.total', 1)
            ->where('orders.data.0.reference', $completed->reference));
});

it('searches orders by reference and by customer email', function (Closure $searchFor) {
    $order = ($this->placeOrder)(User::factory()->create(['email' => 'ana@example.com']));
    Order::factory()->create();

    $this->get(route('orders.index', ['search' => $searchFor($order)]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('orders.meta.total', 1)
            ->where('orders.data.0.reference', $order->reference));
})->with([
    'reference' => fn (Order $order): string => $order->reference,
    'email' => fn (Order $order): string => 'ana@example',
]);

it('shows an order by its reference with lines and key hints, never codes', function () {
    $order = ($this->placeOrder)();

    $this->get("/orders/{$order->reference}")
        ->assertOk()
        ->assertDontSee('SECRET-00001')
        ->assertInertia(fn (Assert $page) => $page
            ->component('orders/Show')
            ->where('order.reference', $order->reference)
            ->where('order.status.label', 'Pendiente')
            ->where('order.total', 19990)
            ->where('order.items.0.title_name', $this->product->title->name)
            ->where('order.items.0.type.label', $this->product->type->label())
            ->has('order.items.0.keys.0', fn (Assert $key) => $key
                ->where('code_hint', $this->key->code_hint)
                ->where('status', 'Reservada')
                ->missingAll(['code', 'code_hash'])
                ->etc())
            ->where('canCancel', true)
            ->where('canMarkPaid', false));
});

it('does not find an order by its id', function () {
    $order = ($this->placeOrder)();

    $this->get("/orders/{$order->id}")->assertNotFound();
});

it('cancels a pending order', function () {
    $order = ($this->placeOrder)();

    $this->post(route('orders.cancel', $order))
        ->assertRedirect(route('orders.show', $order))
        ->assertInertiaFlash('toast.message', 'Pedido cancelado.');

    expect($order->fresh()?->status)->toBe(OrderStatus::Cancelled)
        ->and($this->key->fresh()?->status)->toBe(ProductKeyStatus::Available);
});

it('refuses to cancel an order that is not pending', function () {
    $order = Order::factory()->completed()->create();

    $this->from(route('orders.show', $order))
        ->post(route('orders.cancel', $order))
        ->assertRedirect(route('orders.show', $order))
        ->assertSessionHasErrors(['order' => 'Solo se puede cancelar un pedido pendiente.']);
});

it('hides manual payments unless they are enabled', function () {
    $order = ($this->placeOrder)();

    $this->post(route('orders.mark-paid', $order))->assertNotFound();

    expect($order->fresh()?->status)->toBe(OrderStatus::Pending);
});

describe('with manual payments enabled', function () {
    beforeEach(fn () => config(['orders.manual_payments' => true]));

    it('offers to mark pending orders as paid', function () {
        $order = ($this->placeOrder)();

        $this->get(route('orders.show', $order))
            ->assertInertia(fn (Assert $page) => $page->where('canMarkPaid', true));
    });

    it('marks an order as paid', function () {
        $order = ($this->placeOrder)();

        $this->post(route('orders.mark-paid', $order))
            ->assertRedirect(route('orders.show', $order))
            ->assertInertiaFlash('toast.message', 'Pago registrado.');

        expect($order->fresh()?->status)->toBe(OrderStatus::Completed)
            ->and($this->key->fresh()?->status)->toBe(ProductKeyStatus::Sold);
    });

    it('explains why an expired order without stock cannot be paid', function () {
        $order = ($this->placeOrder)();
        $this->travel(16)->minutes();
        app(ExpireOrders::class)->handle();
        ($this->placeOrder)();

        $this->from(route('orders.show', $order))
            ->post(route('orders.mark-paid', $order))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHasErrors(['order' => 'El pedido no se puede pagar: expiró y ya no hay stock para reservarlo.']);
    });

    it('explains why a cancelled order cannot be paid', function () {
        $order = Order::factory()->cancelled()->create();

        $this->from(route('orders.show', $order))
            ->post(route('orders.mark-paid', $order))
            ->assertSessionHasErrors(['order' => 'El pedido no se puede pagar: está cancelado.']);
    });
});

it('is forbidden to customers', function () {
    $order = ($this->placeOrder)();
    $this->actingAs(User::factory()->create());

    $this->get(route('orders.index'))->assertForbidden();
    $this->get(route('orders.show', $order))->assertForbidden();
    $this->post(route('orders.cancel', $order))->assertForbidden();
});
