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
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Http::preventStrayRequests();
    $this->digital = Product::factory()->published()->create(['price' => 19990]);
    $this->key = ProductKey::factory()->for($this->digital)->create();
    $this->placeOrder = fn () => app(PlaceOrder::class)
        ->handle(User::factory()->create(), [new OrderLineData($this->digital->id, 1)]);
    $this->order = ($this->placeOrder)();
    $this->attempt = PaymentAttempt::factory()->for($this->order)->create(['token' => webpayToken()]);
    $this->buyOrder = "{$this->order->reference}-1";
    $this->committed = webpayTransaction(['buy_order' => $this->buyOrder, 'session_id' => $this->order->reference]);
    $this->fakeCommit = fn (mixed $response) => Http::fake([webpayUrl(webpayToken()) => $response]);
    $this->resultUrl = fn (string $status) => "http://storefront.test/pedidos/{$this->order->reference}?estado={$status}";
    $this->expireOrder = function (): void {
        $this->travel(16)->minutes();
        app(ExpireOrders::class)->handle();
    };
});

function assertNoCommit(): void
{
    Http::assertNotSent(fn (Request $request): bool => $request->method() === 'PUT');
}

it('commits a paid return, records the receipt and delivers', function (string $method) {
    ($this->fakeCommit)(Http::response($this->committed));

    $this->call($method, route('payments.webpay.return'), ['token_ws' => webpayToken()])
        ->assertRedirect(($this->resultUrl)('authorized'));

    $attempt = $this->attempt->fresh();
    expect($attempt?->status)->toBe(PaymentAttemptStatus::Authorized)
        ->and($attempt?->authorization_code)->toBe('1213')
        ->and($attempt?->payment_type_code)->toBe('VN')
        ->and($attempt?->card_last_digits)->toBe('6623')
        ->and($attempt?->transaction_date?->toIso8601ZuluString())->toBe('2026-11-20T15:30:12Z')
        ->and($this->order->fresh()?->status)->toBe(OrderStatus::Completed)
        ->and($this->key->fresh()?->status)->toBe(ProductKeyStatus::Sold);
    Http::assertSentCount(1);
})->with(['GET', 'POST']);

it('accepts the return without a CSRF token', function () {
    expect(Route::getRoutes()->getByName('payments.webpay.return')?->excludedMiddleware())
        ->toContain(PreventRequestForgery::class);
});

it('leaves the order pending when the bank declines the payment', function () {
    ($this->fakeCommit)(Http::response(webpayTransaction([
        'buy_order' => $this->buyOrder,
        'status' => 'FAILED',
        'response_code' => -1,
    ])));

    $this->post(route('payments.webpay.return'), ['token_ws' => webpayToken()])
        ->assertRedirect(($this->resultUrl)('rejected'));

    expect($this->attempt->fresh()?->status)->toBe(PaymentAttemptStatus::Rejected)
        ->and($this->attempt->fresh()?->response_code)->toBe(-1)
        ->and($this->order->fresh()?->status)->toBe(OrderStatus::Pending)
        ->and($this->key->fresh()?->status)->toBe(ProductKeyStatus::Reserved);
});

it('refunds at once a charge that does not match the attempt', function (array $mismatch) {
    Http::fake([
        webpayUrl(webpayToken()) => Http::response([...$this->committed, ...$mismatch]),
        webpayUrl(webpayToken(), '/refunds') => Http::response(['type' => 'REVERSED']),
    ]);

    $this->post(route('payments.webpay.return'), ['token_ws' => webpayToken()]);

    $attempt = $this->attempt->fresh();
    expect($attempt?->status)->toBe(PaymentAttemptStatus::Refunded)
        ->and($attempt?->refund_type)->toBe(RefundType::Reversed)
        ->and($this->order->fresh()?->status)->toBe(OrderStatus::Pending);
    Http::assertSent(fn (Request $request): bool => $request->url() === webpayUrl(webpayToken(), '/refunds')
        && $request['amount'] === ($mismatch['amount'] ?? 19990));
})->with([
    'amount' => [['amount' => 1000]],
    'buy order' => [['buy_order' => 'NK-OTHER-1']],
]);

it('reopens and charges an order that expired while the customer paid', function () {
    ($this->expireOrder)();
    ($this->fakeCommit)(Http::response($this->committed));

    $this->post(route('payments.webpay.return'), ['token_ws' => webpayToken()]);

    expect($this->attempt->fresh()?->status)->toBe(PaymentAttemptStatus::Authorized)
        ->and($this->order->fresh()?->status)->toBe(OrderStatus::Completed)
        ->and($this->key->fresh()?->status)->toBe(ProductKeyStatus::Sold);
});

it('never commits an order that can no longer take the payment', function (Closure $makeUnpayable) {
    Http::fake();
    $makeUnpayable->call($this);

    $this->post(route('payments.webpay.return'), ['token_ws' => webpayToken()])
        ->assertRedirect(($this->resultUrl)('aborted'));

    expect($this->attempt->fresh()?->status)->toBe(PaymentAttemptStatus::Aborted);
    Http::assertNothingSent();
})->with([
    'expired without stock' => [function () {
        ($this->expireOrder)();
        ($this->placeOrder)();
    }],
    'cancelled' => [fn () => app(CancelOrder::class)->handle($this->order)],
    'paid by another attempt' => [fn () => PaymentAttempt::factory()->for($this->order)->authorized()->create(['number' => 2])
        ->order->update(['status' => OrderStatus::Paid, 'paid_at' => now()])],
]);

it('cancels the attempt when the customer cancels in Webpay, without committing', function (array $params) {
    Http::fake();

    $this->post(route('payments.webpay.return'), $params)
        ->assertRedirect(($this->resultUrl)('cancelled'));

    expect($this->attempt->fresh()?->status)->toBe(PaymentAttemptStatus::Cancelled)
        ->and($this->order->fresh()?->status)->toBe(OrderStatus::Pending);
    Http::assertNothingSent();
})->with([
    'cancelled' => [fn () => ['TBK_TOKEN' => webpayToken(), 'TBK_ORDEN_COMPRA' => test()->buyOrder, 'TBK_ID_SESION' => test()->order->reference]],
    'back from an error page' => [fn () => ['token_ws' => webpayToken(), 'TBK_TOKEN' => webpayToken(), 'TBK_ORDEN_COMPRA' => test()->buyOrder, 'TBK_ID_SESION' => test()->order->reference]],
]);

it('ignores a cancellation whose token does not match the buy order', function () {
    Http::fake();

    $this->get(route('payments.webpay.return', ['TBK_TOKEN' => webpayToken('other'), 'TBK_ORDEN_COMPRA' => $this->buyOrder]))
        ->assertRedirect('http://storefront.test/pedidos?estado=desconocido');

    expect($this->attempt->fresh()?->status)->toBe(PaymentAttemptStatus::Initialized);
});

it('leaves a timed-out form to reconciliation', function () {
    Http::fake();

    $this->post(route('payments.webpay.return'), ['TBK_ORDEN_COMPRA' => $this->buyOrder, 'TBK_ID_SESION' => $this->order->reference])
        ->assertRedirect(($this->resultUrl)('initialized'));

    expect($this->attempt->fresh()?->status)->toBe(PaymentAttemptStatus::Initialized);
    Http::assertNothingSent();
});

it('sends nothing for an unknown or malformed return', function (array $params) {
    Http::fake();

    $this->post(route('payments.webpay.return'), $params)
        ->assertRedirect('http://storefront.test/pedidos?estado=desconocido');

    expect($this->attempt->fresh()?->status)->toBe(PaymentAttemptStatus::Initialized);
    Http::assertNothingSent();
})->with([
    'unknown token' => [['token_ws' => webpayToken('unknown')]],
    'malformed token' => [['token_ws' => '../'.webpayToken()]],
    'nothing' => [[]],
]);

it('leaves the attempt open when the commit result is unknown', function () {
    ($this->fakeCommit)(Http::failedConnection());

    $this->post(route('payments.webpay.return'), ['token_ws' => webpayToken()])
        ->assertRedirect(($this->resultUrl)('initialized'));

    expect($this->attempt->fresh()?->status)->toBe(PaymentAttemptStatus::Initialized)
        ->and($this->order->fresh()?->status)->toBe(OrderStatus::Pending);
});

it('rolls back the reopening when the commit result is unknown', function () {
    ($this->expireOrder)();
    ($this->fakeCommit)(Http::failedConnection());

    $this->post(route('payments.webpay.return'), ['token_ws' => webpayToken()]);

    expect($this->order->fresh()?->status)->toBe(OrderStatus::Expired)
        ->and($this->key->fresh()?->status)->toBe(ProductKeyStatus::Available);
});

it('answers a repeated return without committing again', function () {
    ($this->fakeCommit)(Http::response($this->committed));
    $this->post(route('payments.webpay.return'), ['token_ws' => webpayToken()]);

    $this->post(route('payments.webpay.return'), ['token_ws' => webpayToken()])
        ->assertRedirect(($this->resultUrl)('authorized'));

    Http::assertSentCount(1);
});

it('sends the admin back to the order in the panel when manual payments are on', function () {
    config(['orders.manual_payments' => true]);
    ($this->fakeCommit)(Http::response($this->committed));

    $this->post(route('payments.webpay.return'), ['token_ws' => webpayToken()])
        ->assertRedirect(route('orders.show', $this->order));
});
