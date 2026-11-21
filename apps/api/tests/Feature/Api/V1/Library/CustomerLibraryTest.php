<?php

use App\Actions\Orders\MarkOrderPaid;
use App\Actions\Orders\OrderLineData;
use App\Actions\Orders\PlaceOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductKey;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->withHeaders(fromStorefront());
    $this->customer = User::factory()->create();
    $this->digital = Product::factory()->published()->create(['price' => 19990]);
    ProductKey::factory()->for($this->digital)->create(['code' => 'SECRET-00001-ABCD']);
    ProductKey::factory()->for($this->digital)->create(['code' => 'SECRET-00002-EFGH']);
    ProductKey::factory()->for($this->digital)->create(['code' => 'SECRET-00003-IJKL']);
    ProductKey::factory()->for($this->digital)->create(['code' => 'SECRET-00004-MNOP']);
    $this->box = Product::factory()->physical()->published()->create(['stock' => 3]);
    $this->placePaidOrder = function (?User $customer = null): Order {
        $order = app(PlaceOrder::class)->handle($customer ?? $this->customer, [
            new OrderLineData($this->digital->id, 2),
            new OrderLineData($this->box->id, 1),
        ]);
        app(MarkOrderPaid::class)->handle($order);

        return $order->refresh();
    };
});

it('lists the key lines of paid orders with hints, never codes', function () {
    $order = ($this->placePaidOrder)();

    $this->actingAs($this->customer, 'customer')
        ->getJson(route('api.v1.library.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.order_reference', $order->reference)
        ->assertJsonPath('data.0.product.slug', $this->digital->slug)
        ->assertJsonPath('data.0.quantity', 2)
        ->assertJsonPath('data.0.keys.0.revealed', false)
        ->assertJsonCount(2, 'data.0.keys')
        ->assertDontSee('SECRET-')
        ->assertDontSee('"code"', escape: false)
        ->assertDontSee('"id"', escape: false);
});

it('leaves out pending orders and other customers', function () {
    app(PlaceOrder::class)->handle($this->customer, [new OrderLineData($this->digital->id, 1)]);
    ($this->placePaidOrder)(User::factory()->create());

    $this->actingAs($this->customer, 'customer')
        ->getJson(route('api.v1.library.index'))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('reveals the codes of a line and records the first reveal only', function () {
    $order = ($this->placePaidOrder)();
    $this->freezeSecond();
    $url = route('api.v1.library.keys', [$order->reference, $this->digital->slug]);

    $this->actingAs($this->customer, 'customer')
        ->postJson($url)
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('data.keys.*.code', ['SECRET-00001-ABCD', 'SECRET-00002-EFGH']);

    $firstReveal = now()->toImmutable();
    expect(ProductKey::query()->whereNotNull('revealed_at')->pluck('revealed_at')->map(fn ($at) => $at?->equalTo($firstReveal))->all())->toBe([true, true]);

    $this->travel(5)->minutes();
    $this->postJson($url)->assertOk();

    expect(ProductKey::query()->whereNotNull('revealed_at')->pluck('revealed_at')->map(fn ($at) => $at?->equalTo($firstReveal))->all())->toBe([true, true]);

    $this->getJson(route('api.v1.library.index'))->assertJsonPath('data.0.keys.0.revealed', true);
});

it('reveals nothing for another customer, an unpaid order or a physical line', function (Closure $url) {
    $this->actingAs($this->customer, 'customer')
        ->postJson($url->call($this))
        ->assertNotFound();

    expect(ProductKey::query()->whereNotNull('revealed_at')->count())->toBe(0);
})->with([
    'another customer' => [function (): string {
        $order = ($this->placePaidOrder)(User::factory()->create());

        return route('api.v1.library.keys', [$order->reference, $this->digital->slug]);
    }],
    'pending order' => [function (): string {
        $order = app(PlaceOrder::class)->handle($this->customer, [new OrderLineData($this->digital->id, 1)]);

        return route('api.v1.library.keys', [$order->reference, $this->digital->slug]);
    }],
    'physical line' => [function (): string {
        $order = ($this->placePaidOrder)();

        return route('api.v1.library.keys', [$order->reference, $this->box->slug]);
    }],
]);

it('limits reveals to 30 a minute', function () {
    $order = ($this->placePaidOrder)();
    $url = route('api.v1.library.keys', [$order->reference, $this->digital->slug]);
    $this->actingAs($this->customer, 'customer');

    foreach (range(1, 30) as $attempt) {
        $this->postJson($url)->assertOk();
    }

    $this->postJson($url)->assertTooManyRequests();
});
