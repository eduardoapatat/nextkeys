<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentOrigin;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\ProductKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
    $this->withHeaders(fromStorefront());
    $this->customer = User::factory()->create();
    $this->product = Product::factory()->published()->create(['price' => 19990]);
    ProductKey::factory()->for($this->product)->count(3)->sequence(fn (Sequence $sequence): array => ['code' => "SECRET-CODE-{$sequence->index}"])->create();
});

it('places an order from slugs for the signed in customer', function () {
    $response = $this->actingAs($this->customer, 'customer')
        ->postJson(route('api.v1.orders.store'), ['items' => [['slug' => $this->product->slug, 'quantity' => 2]]])
        ->assertCreated()
        ->assertJsonPath('data.status', ['slug' => 'pending', 'name' => 'Pendiente'])
        ->assertJsonPath('data.total', 39980)
        ->assertJsonPath('data.can_pay', true)
        ->assertJsonPath('data.items.0.product_slug', $this->product->slug)
        ->assertJsonPath('data.items.0.quantity', 2)
        ->assertJsonPath('data.last_payment', null);

    $order = $this->customer->orders()->sole();
    $response->assertJsonPath('data.reference', $order->reference);
    expect($order->status)->toBe(OrderStatus::Pending);
});

it('asks for a verified email before placing an order', function () {
    $unverified = User::factory()->unverified()->create();

    $this->actingAs($unverified, 'customer')
        ->postJson(route('api.v1.orders.store'), ['items' => [['slug' => $this->product->slug, 'quantity' => 1]]])
        ->assertForbidden()
        ->assertJsonPath('message', 'Verifica tu correo antes de pagar.');

    expect(Order::query()->count())->toBe(0);
});

it('rejects a product that is not on sale', function (Closure $slug) {
    $this->actingAs($this->customer, 'customer')
        ->postJson(route('api.v1.orders.store'), ['items' => [
            ['slug' => $this->product->slug, 'quantity' => 1],
            ['slug' => $slug(), 'quantity' => 1],
        ]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.1.slug' => 'Este producto ya no está disponible.']);
})->with([
    'unknown' => [fn (): string => 'does-not-exist'],
    'draft' => [fn (): string => Product::factory()->create()->slug],
]);

it('validates the items', function () {
    $this->actingAs($this->customer, 'customer')
        ->postJson(route('api.v1.orders.store'), ['items' => []])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items']);

    $this->actingAs($this->customer, 'customer')
        ->postJson(route('api.v1.orders.store'), ['items' => [['slug' => $this->product->slug, 'quantity' => 6]]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.quantity' => 'La cantidad debe estar entre 1 y 5.']);
});

it('ships a physical product to one of the customer\'s addresses and charges the fee', function () {
    config(['orders.shipping_cost' => 3990]);
    $box = Product::factory()->physical()->published()->create(['price' => 49990, 'stock' => 2]);
    $address = CustomerAddress::factory()->for($this->customer)->create(['commune' => 'Ñuñoa', 'phone' => '+56912345678']);

    $this->actingAs($this->customer, 'customer')
        ->postJson(route('api.v1.orders.store'), [
            'items' => [['slug' => $box->slug, 'quantity' => 1]],
            'shipping_address' => $address->public_id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.shipping_cost', 3990)
        ->assertJsonPath('data.total', 49990 + 3990)
        ->assertJsonPath('data.shipment.status', ['slug' => 'pending', 'name' => 'Por despachar'])
        ->assertJsonPath('data.shipment.address.commune', 'Ñuñoa')
        ->assertJsonPath('data.shipment.address.phone', '+56912345678')
        ->assertJsonPath('data.shipment.carrier', null)
        ->assertJsonPath('data.shipment.tracking_url', null)
        ->assertJsonPath('data.shipment.returnable_until', null)
        ->assertJsonMissingPath('data.shipment.id')
        ->assertJsonMissingPath('data.shipment.address.public_id');
});

it('answers a digital order with no shipment', function () {
    $this->actingAs($this->customer, 'customer')
        ->postJson(route('api.v1.orders.store'), ['items' => [['slug' => $this->product->slug, 'quantity' => 1]]])
        ->assertCreated()
        ->assertJsonPath('data.shipping_cost', 0)
        ->assertJsonPath('data.shipment', null);
});

it('validates the shipping address', function (Closure $address, string $message) {
    $box = Product::factory()->physical()->published()->create(['stock' => 2]);

    $this->actingAs($this->customer, 'customer')
        ->postJson(route('api.v1.orders.store'), array_filter([
            'items' => [['slug' => $box->slug, 'quantity' => 1]],
            'shipping_address' => $address(),
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['shipping_address' => $message]);

    expect(Order::query()->count())->toBe(0);
})->with([
    'missing' => [fn (): null => null, 'Elige una dirección de despacho para los productos físicos.'],
    'unknown' => [fn (): string => '01JDZZZZZZZZZZZZZZZZZZZZZZ', 'Esta dirección no existe.'],
    'another customer\'s' => [fn (): string => CustomerAddress::factory()->create()->public_id, 'Esta dirección no existe.'],
]);

it('refuses a second pending order', function () {
    $this->actingAs($this->customer, 'customer');
    $this->postJson(route('api.v1.orders.store'), ['items' => [['slug' => $this->product->slug, 'quantity' => 1]]])->assertCreated();

    $this->postJson(route('api.v1.orders.store'), ['items' => [['slug' => $this->product->slug, 'quantity' => 1]]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['order']);
});

it('lists and shows only the customer own orders', function () {
    $order = Order::factory()->for($this->customer)->create();
    $other = Order::factory()->create();

    $this->actingAs($this->customer, 'customer');

    $this->getJson(route('api.v1.orders.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.reference', $order->reference);

    $this->getJson(route('api.v1.orders.show', $order->reference))->assertOk();
    $this->getJson(route('api.v1.orders.show', $other->reference))->assertNotFound();
    $this->postJson(route('api.v1.orders.payments.store', $other->reference))->assertNotFound();
    $this->postJson(route('api.v1.orders.cancel', $other->reference))->assertNotFound();
});

it('starts a Webpay payment for the order', function () {
    Http::fake([webpayUrl() => Http::response([
        'token' => webpayToken(),
        'url' => 'https://webpay3gint.transbank.cl/webpayserver/initTransaction',
    ])]);
    $this->actingAs($this->customer, 'customer');
    $reference = $this->postJson(route('api.v1.orders.store'), ['items' => [['slug' => $this->product->slug, 'quantity' => 1]]])
        ->json('data.reference');

    $this->postJson(route('api.v1.orders.payments.store', $reference))
        ->assertCreated()
        ->assertExactJson(['data' => [
            'url' => 'https://webpay3gint.transbank.cl/webpayserver/initTransaction',
            'token' => webpayToken(),
        ]]);

    $attempt = Order::query()->where('reference', $reference)->sole()->paymentAttempts()->sole();
    expect($attempt->origin)->toBe(PaymentOrigin::Storefront)
        ->and($attempt->status)->toBe(PaymentAttemptStatus::Initialized);
});

it('explains why an order cannot be paid', function () {
    $order = Order::factory()->for($this->customer)->create(['status' => OrderStatus::Cancelled, 'cancelled_at' => now()]);

    $this->actingAs($this->customer, 'customer')
        ->postJson(route('api.v1.orders.payments.store', $order->reference))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['order' => 'El pedido no se puede pagar: está cancelado.']);
});

it('answers 503 when Webpay does not answer', function () {
    Http::fake([webpayUrl() => Http::response(['error_message' => 'boom'], 500)]);
    $this->actingAs($this->customer, 'customer');
    $reference = $this->postJson(route('api.v1.orders.store'), ['items' => [['slug' => $this->product->slug, 'quantity' => 1]]])
        ->json('data.reference');

    $this->postJson(route('api.v1.orders.payments.store', $reference))
        ->assertServiceUnavailable()
        ->assertExactJson(['message' => 'No pudimos conectar con Webpay. Intenta de nuevo.']);
});

it('cancels a pending order', function () {
    $this->actingAs($this->customer, 'customer');
    $reference = $this->postJson(route('api.v1.orders.store'), ['items' => [['slug' => $this->product->slug, 'quantity' => 1]]])
        ->json('data.reference');

    $this->postJson(route('api.v1.orders.cancel', $reference))
        ->assertOk()
        ->assertJsonPath('data.status.slug', 'cancelled')
        ->assertJsonPath('data.can_pay', false);
});

it('never exposes keys, ids or the buy order', function () {
    Http::fake([webpayUrl() => Http::response([
        'token' => webpayToken(),
        'url' => 'https://webpay3gint.transbank.cl/webpayserver/initTransaction',
    ])]);
    $this->actingAs($this->customer, 'customer');
    $reference = $this->postJson(route('api.v1.orders.store'), ['items' => [['slug' => $this->product->slug, 'quantity' => 2]]])
        ->json('data.reference');
    $this->postJson(route('api.v1.orders.payments.store', $reference))->assertCreated();

    foreach ([route('api.v1.orders.index'), route('api.v1.orders.show', $reference)] as $url) {
        $this->getJson($url)
            ->assertOk()
            ->assertDontSee('SECRET-CODE')
            ->assertDontSee('"id"', escape: false)
            ->assertDontSee('"code', escape: false)
            ->assertDontSee("{$reference}-1");
    }
});

it('shows the authorized payment as the receipt of a paid order, never a later unfinished attempt', function () {
    $order = Order::factory()->for($this->customer)->paid()->create();
    PaymentAttempt::factory()->for($order)->authorized()->create(['number' => 1, 'authorization_code' => '1213']);
    PaymentAttempt::factory()->for($order)->create(['number' => 2]);

    $this->actingAs($this->customer, 'customer')
        ->getJson(route('api.v1.orders.show', $order->reference))
        ->assertOk()
        ->assertJsonPath('data.last_payment.status.slug', 'authorized')
        ->assertJsonPath('data.last_payment.authorization_code', '1213');
});

it('shows no receipt for an order paid without an authorized attempt', function () {
    $order = Order::factory()->for($this->customer)->paid()->create();
    PaymentAttempt::factory()->for($order)->create();

    $this->actingAs($this->customer, 'customer')
        ->getJson(route('api.v1.orders.show', $order->reference))
        ->assertOk()
        ->assertJsonPath('data.last_payment', null);
});
