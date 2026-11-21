<?php

use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\ExpireOrders;
use App\Actions\Orders\OrderLineData;
use App\Actions\Orders\PlaceOrder;
use App\Enums\OrderStatus;
use App\Enums\PaymentAttemptStatus;
use App\Enums\ProductKeyStatus;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\ProductKey;
use App\Models\User;
use App\Payments\RefundType;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
    $this->digital = Product::factory()->published()->create(['price' => 19990]);
    $this->key = ProductKey::factory()->for($this->digital)->create();
    $this->placeOrder = fn () => app(PlaceOrder::class)
        ->handle(User::factory()->create(), [new OrderLineData($this->digital->id, 1)]);
    $this->order = ($this->placeOrder)();
    $this->attempt = PaymentAttempt::factory()->for($this->order)->create(['token' => webpayToken()]);
    $this->charged = webpayTransaction(['buy_order' => "{$this->order->reference}-1"]);
    $this->fakeStatus = fn (array $body) => Http::fake([
        webpayUrl(webpayToken()) => Http::response($body),
        webpayUrl(webpayToken(), '/refunds') => Http::response(['type' => 'REVERSED']),
    ]);
    $this->reconcile = function (): void {
        $this->artisan('payments:reconcile')->assertSuccessful();
    };
});

it('leaves recent attempts alone, since the customer may still be paying', function () {
    Http::fake();
    $this->travel(14)->minutes();

    ($this->reconcile)();

    expect($this->attempt->fresh()?->status)->toBe(PaymentAttemptStatus::Initialized);
    Http::assertNothingSent();
});

it('records a charge nobody recorded when the order can still take it', function () {
    ($this->fakeStatus)($this->charged);
    $this->travel(16)->minutes();

    ($this->reconcile)();

    expect($this->attempt->fresh()?->status)->toBe(PaymentAttemptStatus::Authorized)
        ->and($this->attempt->fresh()?->authorization_code)->toBe('1213')
        ->and($this->order->fresh()?->status)->toBe(OrderStatus::Completed)
        ->and($this->key->fresh()?->status)->toBe(ProductKeyStatus::Sold);
    Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/refunds'));
});

it('reopens an expired order to record its charge when the stock is still there', function () {
    ($this->fakeStatus)($this->charged);
    $this->travel(16)->minutes();
    app(ExpireOrders::class)->handle();

    ($this->reconcile)();

    expect($this->attempt->fresh()?->status)->toBe(PaymentAttemptStatus::Authorized)
        ->and($this->order->fresh()?->status)->toBe(OrderStatus::Completed);
});

it('refunds automatically a charge the order cannot take', function (Closure $makeUnpayable) {
    ($this->fakeStatus)($this->charged);
    $makeUnpayable->call($this);

    ($this->reconcile)();

    $attempt = $this->attempt->fresh();
    expect($attempt?->status)->toBe(PaymentAttemptStatus::Refunded)
        ->and($attempt?->refund_type)->toBe(RefundType::Reversed)
        ->and($attempt?->refunded_amount)->toBe(19990)
        ->and($attempt?->authorization_code)->toBe('1213');
    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === webpayUrl(webpayToken(), '/refunds')
        && $request['amount'] === 19990);
})->with([
    'cancelled' => [function () {
        app(CancelOrder::class)->handle($this->order);
        $this->travel(16)->minutes();
    }],
    'expired without stock' => [function () {
        $this->travel(16)->minutes();
        app(ExpireOrders::class)->handle();
        ($this->placeOrder)();
    }],
]);

it('refunds a charge that does not match the attempt', function () {
    ($this->fakeStatus)([...$this->charged, 'amount' => 1000]);
    $this->travel(16)->minutes();

    ($this->reconcile)();

    expect($this->attempt->fresh()?->status)->toBe(PaymentAttemptStatus::Refunded)
        ->and($this->order->fresh()?->status)->toBe(OrderStatus::Pending);
});

it('closes the attempts the gateway never charged', function (array $body, PaymentAttemptStatus $status) {
    ($this->fakeStatus)($body);
    $this->travel(16)->minutes();

    ($this->reconcile)();

    expect($this->attempt->fresh()?->status)->toBe($status)
        ->and($this->order->fresh()?->status)->toBe(OrderStatus::Pending);
    Http::assertNotSent(fn (Request $request): bool => $request->method() !== 'GET');
})->with([
    'never committed' => [['amount' => 19990, 'status' => 'INITIALIZED', 'buy_order' => 'NK-X-1'], PaymentAttemptStatus::Aborted],
    'declined' => [['amount' => 19990, 'status' => 'FAILED', 'buy_order' => 'NK-X-1', 'response_code' => -1], PaymentAttemptStatus::Rejected],
]);

it('records a charge refunded elsewhere', function () {
    ($this->fakeStatus)([...$this->charged, 'status' => 'REVERSED']);
    $this->travel(16)->minutes();

    ($this->reconcile)();

    expect($this->attempt->fresh()?->status)->toBe(PaymentAttemptStatus::Refunded)
        ->and($this->attempt->fresh()?->refund_type)->toBe(RefundType::Reversed);
    Http::assertSentCount(1);
});

it('aborts without a call an attempt with no token or too old to look up', function (Closure $prepare) {
    Http::fake();
    $prepare->call($this);

    ($this->reconcile)();

    expect($this->attempt->fresh()?->status)->toBe(PaymentAttemptStatus::Aborted);
    Http::assertNothingSent();
})->with([
    'no token' => [function () {
        $this->attempt->update(['token' => null]);
        $this->travel(16)->minutes();
    }],
    'older than 7 days' => [fn () => $this->travel(8)->days()],
]);

it('retries on the next run when the gateway fails', function () {
    Http::fake([webpayUrl(webpayToken()) => Http::sequence()
        ->pushFailedConnection()
        ->push($this->charged)]);
    $this->travel(16)->minutes();

    ($this->reconcile)();
    expect($this->attempt->fresh()?->status)->toBe(PaymentAttemptStatus::Initialized);

    ($this->reconcile)();
    expect($this->attempt->fresh()?->status)->toBe(PaymentAttemptStatus::Authorized);
});

it('leaves finished attempts alone', function () {
    Http::fake();
    $this->attempt->finish(PaymentAttemptStatus::Cancelled);
    $this->travel(16)->minutes();

    ($this->reconcile)();

    Http::assertNothingSent();
});
