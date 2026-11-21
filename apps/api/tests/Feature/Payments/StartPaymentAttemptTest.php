<?php

use App\Actions\Orders\ExpireOrders;
use App\Actions\Orders\OrderCannotBePaid;
use App\Actions\Orders\OrderLineData;
use App\Actions\Orders\PlaceOrder;
use App\Actions\Payments\StartPaymentAttempt;
use App\Enums\OrderStatus;
use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentOrigin;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\ProductKey;
use App\Models\User;
use App\Payments\PaymentGatewayException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
    $this->digital = Product::factory()->published()->create(['price' => 19990]);
    ProductKey::factory()->for($this->digital)->create();
    $this->order = app(PlaceOrder::class)
        ->handle(User::factory()->create(), [new OrderLineData($this->digital->id, 1)]);
    $this->fakeCreate = fn (string $token = 'a') => Http::fake([webpayUrl() => Http::response([
        'token' => webpayToken($token),
        'url' => 'https://webpay3gint.transbank.cl/webpayserver/initTransaction',
    ])]);
});

it('creates a transaction for the order total with a buy order per attempt', function () {
    ($this->fakeCreate)();

    $redirect = app(StartPaymentAttempt::class)->handle($this->order, PaymentOrigin::Storefront);

    $attempt = $this->order->paymentAttempts()->sole();
    expect($redirect->token)->toBe(webpayToken())
        ->and($attempt->number)->toBe(1)
        ->and($attempt->buy_order)->toBe("{$this->order->reference}-1")
        ->and($attempt->origin)->toBe(PaymentOrigin::Storefront)
        ->and($attempt->token)->toBe(webpayToken())
        ->and($attempt->amount)->toBe(19990)
        ->and($attempt->status)->toBe(PaymentAttemptStatus::Initialized);
    Http::assertSent(fn (Request $request): bool => $request['buy_order'] === "{$this->order->reference}-1"
        && $request['session_id'] === $this->order->reference
        && $request['amount'] === 19990
        && $request['return_url'] === route('payments.webpay.return'));
});

it('numbers the next attempt after the previous ones', function () {
    ($this->fakeCreate)();
    PaymentAttempt::factory()->for($this->order)->create(['status' => PaymentAttemptStatus::Aborted, 'token' => null, 'finished_at' => now()]);

    app(StartPaymentAttempt::class)->handle($this->order, PaymentOrigin::Storefront);

    expect($this->order->paymentAttempts()->pluck('buy_order')->all())
        ->toBe(["{$this->order->reference}-1", "{$this->order->reference}-2"]);
});

it('extends the hold to cover the payment form', function () {
    ($this->fakeCreate)();
    $this->travel(10)->minutes();
    $this->freezeTime();

    app(StartPaymentAttempt::class)->handle($this->order, PaymentOrigin::Storefront);

    expect($this->order->fresh()?->expires_at->toDateTimeString())->toBe(now()->addMinutes(11)->toDateTimeString());
});

it('keeps a hold that already covers the payment form', function () {
    ($this->fakeCreate)();
    $expiresAt = $this->order->expires_at->toDateTimeString();

    app(StartPaymentAttempt::class)->handle($this->order, PaymentOrigin::Storefront);

    expect($this->order->fresh()?->expires_at->toDateTimeString())->toBe($expiresAt);
});

it('reopens an expired order whose stock is still there', function () {
    ($this->fakeCreate)();
    $this->travel(16)->minutes();
    app(ExpireOrders::class)->handle();

    app(StartPaymentAttempt::class)->handle($this->order, PaymentOrigin::Storefront);

    expect($this->order->fresh()?->status)->toBe(OrderStatus::Pending);
});

it('refuses a sixth attempt', function () {
    Http::fake();
    foreach (range(1, 5) as $number) {
        PaymentAttempt::factory()->for($this->order)->create(['number' => $number, 'status' => PaymentAttemptStatus::Cancelled, 'finished_at' => now()]);
    }

    expect(validationErrorsOf(fn () => app(StartPaymentAttempt::class)->handle($this->order, PaymentOrigin::Storefront)))
        ->toBe(['order' => ['El pedido alcanzó el máximo de 5 intentos de pago. Crea un pedido nuevo.']]);
    Http::assertNothingSent();
});

it('refuses an order that cannot be charged', function (Closure $makeOrder) {
    Http::fake();

    expect(fn () => app(StartPaymentAttempt::class)->handle($makeOrder(), PaymentOrigin::Storefront))->toThrow(OrderCannotBePaid::class);
    expect(PaymentAttempt::query()->count())->toBe(0);
    Http::assertNothingSent();
})->with([
    'paid' => [fn () => Order::factory()->completed()->create()],
    'cancelled' => [fn () => Order::factory()->cancelled()->create()],
]);

it('aborts the attempt, without reusing its buy order, when the gateway fails', function () {
    Http::fake([webpayUrl() => Http::response(['error_message' => 'Internal error'], 500)]);

    expect(fn () => app(StartPaymentAttempt::class)->handle($this->order, PaymentOrigin::Storefront))->toThrow(PaymentGatewayException::class);

    $attempt = $this->order->paymentAttempts()->sole();
    expect($attempt->status)->toBe(PaymentAttemptStatus::Aborted)
        ->and($attempt->token)->toBeNull()
        ->and($attempt->number)->toBe(1);
});
