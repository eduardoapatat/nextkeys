<?php

use App\Actions\Orders\ExpireOrders;
use App\Actions\Orders\OrderLineData;
use App\Actions\Orders\PlaceOrder;
use App\Enums\OrderStatus;
use App\Enums\ProductKeyStatus;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductKey;
use App\Models\User;

beforeEach(function () {
    $this->customer = User::factory()->create();
});

it('places a pending order that reserves keys and holds boxes', function () {
    $this->freezeSecond();
    $digital = Product::factory()->published()->create(['price' => 19990]);
    $keys = ProductKey::factory()->for($digital)->count(3)->create();
    $box = Product::factory()->physical()->published()->create(['price' => 49990, 'stock' => 4]);

    $order = app(PlaceOrder::class)->handle($this->customer, [
        new OrderLineData($digital->id, 2),
        new OrderLineData($box->id, 1),
    ]);

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->user_id)->toBe($this->customer->id)
        ->and($order->reference)->toMatch('/^NK-[0-9A-HJKMNP-TV-Z]{6}$/')
        ->and($order->total)->toBe(19990 * 2 + 49990)
        ->and($order->expires_at->equalTo(now()->addMinutes(15)))->toBeTrue()
        ->and($box->fresh()?->stock)->toBe(3);

    $digitalLine = $order->items()->where('product_id', $digital->id)->sole();
    expect($digitalLine->keys()->pluck('status')->all())->toBe([ProductKeyStatus::Reserved, ProductKeyStatus::Reserved])
        ->and(ProductKey::query()->where('status', ProductKeyStatus::Available)->pluck('id')->all())->toBe([$keys[2]->id]);
});

it('keeps a snapshot of the price and the title name', function () {
    $product = Product::factory()->published()->create(['price' => 19990]);
    ProductKey::factory()->for($product)->create();

    $order = app(PlaceOrder::class)->handle($this->customer, [new OrderLineData($product->id, 1)]);
    $product->update(['price' => 9990]);
    $product->title->update(['name' => 'Renamed']);

    $line = $order->items()->sole();
    expect($line->unit_price)->toBe(19990)
        ->and($line->line_total)->toBe(19990)
        ->and($line->title_name)->not->toBe('Renamed')
        ->and($line->product_type)->toBe(ProductType::DigitalKey);
});

it('writes nothing when one line is short of stock', function () {
    $digital = Product::factory()->published()->create();
    ProductKey::factory()->for($digital)->create();
    $box = Product::factory()->physical()->published()->create(['stock' => 1]);

    $errors = validationErrorsOf(fn () => app(PlaceOrder::class)->handle($this->customer, [
        new OrderLineData($box->id, 1),
        new OrderLineData($digital->id, 2),
    ]));

    expect($errors)->toBe(['items.1.quantity' => ["No hay stock suficiente de {$digital->title->name}."]])
        ->and($box->fresh()?->stock)->toBe(1)
        ->and(ProductKey::query()->where('status', ProductKeyStatus::Available)->count())->toBe(1);
    $this->assertDatabaseCount('orders', 0);
    $this->assertDatabaseCount('order_items', 0);
});

it('rejects physical stock that is short', function () {
    $box = Product::factory()->physical()->published()->create(['stock' => 1]);

    $errors = validationErrorsOf(fn () => app(PlaceOrder::class)->handle($this->customer, [new OrderLineData($box->id, 2)]));

    expect($errors)->toHaveKey('items.0.quantity')
        ->and($box->fresh()?->stock)->toBe(1);
});

it('rejects products that are not published', function (Product $product) {

    $errors = validationErrorsOf(fn () => app(PlaceOrder::class)->handle($this->customer, [new OrderLineData($product->id, 1)]));

    expect($errors)->toBe(['items.0.product_id' => ['Este producto no está disponible.']]);
})->with([
    'draft' => fn () => Product::factory()->physical()->create(['stock' => 5]),
    'archived' => fn () => Product::factory()->physical()->archived()->create(['stock' => 5]),
]);

it('rejects a quantity outside 1 to 5', function (int $quantity) {
    $box = Product::factory()->physical()->published()->create(['stock' => 10]);

    $errors = validationErrorsOf(fn () => app(PlaceOrder::class)->handle($this->customer, [new OrderLineData($box->id, $quantity)]));

    expect($errors)->toBe(['items.0.quantity' => ['La cantidad debe estar entre 1 y 5.']]);
})->with([0, 6]);

it('rejects the same product on two lines', function () {
    $box = Product::factory()->physical()->published()->create(['stock' => 10]);

    $errors = validationErrorsOf(fn () => app(PlaceOrder::class)->handle($this->customer, [
        new OrderLineData($box->id, 1),
        new OrderLineData($box->id, 1),
    ]));

    expect($errors)->toBe(['items.1.product_id' => ['Este producto ya está en el pedido.']]);
});

it('rejects an empty cart', function () {
    $errors = validationErrorsOf(fn () => app(PlaceOrder::class)->handle($this->customer, []));

    expect($errors)->toBe(['items' => ['El carrito está vacío.']]);
});

it('rejects an admin', function () {
    $box = Product::factory()->physical()->published()->create(['stock' => 10]);

    $errors = validationErrorsOf(fn () => app(PlaceOrder::class)->handle(User::factory()->admin()->create(), [new OrderLineData($box->id, 1)]));

    expect($errors)->toBe(['user' => ['Solo los clientes pueden hacer pedidos.']]);
    $this->assertDatabaseCount('orders', 0);
});

it('refuses a second order while the customer has one pending', function () {
    $product = Product::factory()->published()->create();
    ProductKey::factory()->for($product)->count(2)->create();
    $pending = app(PlaceOrder::class)->handle($this->customer, [new OrderLineData($product->id, 1)]);

    expect(validationErrorsOf(fn () => app(PlaceOrder::class)->handle($this->customer, [new OrderLineData($product->id, 1)])))
        ->toBe(['order' => ["Ya tienes un pedido pendiente ({$pending->reference}): págalo o cancélalo antes de hacer otro."]]);

    expect($this->customer->orders()->count())->toBe(1);
});

it('lets a customer order again once the pending order expired', function () {
    $product = Product::factory()->published()->create();
    ProductKey::factory()->for($product)->count(2)->create();
    app(PlaceOrder::class)->handle($this->customer, [new OrderLineData($product->id, 1)]);
    $this->travel(16)->minutes();
    app(ExpireOrders::class)->handle();

    $order = app(PlaceOrder::class)->handle($this->customer, [new OrderLineData($product->id, 1)]);

    expect($order->status)->toBe(OrderStatus::Pending);
});
