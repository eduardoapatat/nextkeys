<?php

use App\Actions\Orders\MarkOrderPaid;
use App\Actions\Orders\OrderLineData;
use App\Actions\Orders\PlaceOrder;
use App\Jobs\OrderPaidEmailFailed;
use App\Jobs\SendOrderPaidEmail;
use App\Mail\OrderPaidMail;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\ProductKey;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->digital = Product::factory()->published()->create(['price' => 19990]);
    $this->key = ProductKey::factory()->for($this->digital)->create(['code' => 'SECRET-00001-ABCD']);
    $this->customer = User::factory()->create(['email' => 'cliente@example.test']);
    $this->order = app(PlaceOrder::class)->handle($this->customer, [new OrderLineData($this->digital->id, 1)]);
    $this->sentMail = function (): OrderPaidMail {
        $sent = Mail::sent(OrderPaidMail::class);
        expect($sent)->toHaveCount(1);

        return $sent->sole();
    };
});

it('queues the confirmation once when the order is paid, not on a repeated confirmation', function () {
    Queue::fake();

    app(MarkOrderPaid::class)->handle($this->order);
    app(MarkOrderPaid::class)->handle($this->order);

    Queue::assertPushed(SendOrderPaidEmail::class, 1);
    Queue::assertPushed(SendOrderPaidEmail::class, fn (SendOrderPaidEmail $job): bool => $job->orderId === $this->order->id);
});

it('sends nothing when the payment rolls back', function () {
    Mail::fake();

    expect(fn () => DB::transaction(function (): void {
        app(MarkOrderPaid::class)->handle($this->order);

        throw new RuntimeException('The outer transaction fails after the payment.');
    }))->toThrow(RuntimeException::class);

    Mail::assertNothingSent();
});

it('sends one email for a paid Webpay return, and none when the return repeats', function () {
    Mail::fake();
    PaymentAttempt::factory()->for($this->order)->create(['token' => webpayToken()]);
    Http::fake([webpayUrl(webpayToken()) => Http::response(webpayTransaction([
        'buy_order' => "{$this->order->reference}-1",
        'session_id' => $this->order->reference,
    ]))]);

    $this->post(route('payments.webpay.return'), ['token_ws' => webpayToken()]);
    $this->post(route('payments.webpay.return'), ['token_ws' => webpayToken()]);

    Mail::assertSent(OrderPaidMail::class, 1);
    Mail::assertSent(OrderPaidMail::class, fn (OrderPaidMail $mail): bool => $mail->hasTo('cliente@example.test'));
});

it('sends one email for a charge the reconciliation records, and none on the next run', function () {
    Mail::fake();
    PaymentAttempt::factory()->for($this->order)->create(['token' => webpayToken()]);
    Http::fake([webpayUrl(webpayToken()) => Http::response(webpayTransaction(['buy_order' => "{$this->order->reference}-1"]))]);
    $this->travel(16)->minutes();

    $this->artisan('payments:reconcile')->assertSuccessful();
    $this->artisan('payments:reconcile')->assertSuccessful();

    Mail::assertSent(OrderPaidMail::class, 1);
});

it('records when the email went out', function () {
    Mail::fake();

    app(MarkOrderPaid::class)->handle($this->order);

    expect($this->order->fresh()?->paid_email_sent_at)->not->toBeNull();
});

it('shows the keys, where to activate them, the receipt and the withdrawal conditions', function () {
    Mail::fake();
    PaymentAttempt::factory()->for($this->order)->authorized()->create();
    $activation = $this->digital->activationService->name ?? $this->digital->platforms->firstOrFail()->name;

    app(MarkOrderPaid::class)->handle($this->order);

    $html = ($this->sentMail)()->render();
    expect($html)
        ->toContain($this->order->reference)
        ->toContain('SECRET-00001-ABCD')
        ->toContain(e($activation))
        ->toContain('$19.990')
        ->toContain('Código de autorización')
        ->toContain('no admiten derecho a retracto')
        ->toContain('http://storefront.test/biblioteca')
        ->not->toContain('Se despachará')
        ->not->toContain('10 días de derecho a retracto');
});

it('lists the physical lines of a mixed order as waiting to ship', function () {
    Mail::fake();
    ProductKey::factory()->for($this->digital)->create(['code' => 'SECRET-00002-EFGH']);
    $box = Product::factory()->physical()->published()->create(['stock' => 2]);
    $order = app(PlaceOrder::class)->handle(User::factory()->create(), [
        new OrderLineData($this->digital->id, 1),
        new OrderLineData($box->id, 1),
    ]);

    app(MarkOrderPaid::class)->handle($order);

    expect(($this->sentMail)()->render())
        ->toContain('SECRET-00002-EFGH')
        ->toContain('Se despachará')
        ->toContain('10 días de derecho a retracto');
});

it('queues only the order id, never a key code', function () {
    config(['queue.default' => 'database']);

    app(MarkOrderPaid::class)->handle($this->order);

    $payload = DB::table('jobs')->sole()->payload;
    expect(json_decode($payload, true)['displayName'])->toBe(SendOrderPaidEmail::class)
        ->and($payload)->not->toContain('SECRET-00001-ABCD')
        ->and(serialize(new SendOrderPaidEmail($this->order->id)))->not->toContain('SECRET-00001-ABCD');
});

it('keeps the codes out of the logs and failed_jobs when sending fails', function () {
    $log = storage_path('logs/order-paid-email-test.log');
    File::delete($log);
    config([
        'queue.default' => 'database',
        'logging.default' => 'single',
        'logging.channels.single.path' => $log,
    ]);
    $this->order->update(['status' => 'paid', 'paid_at' => now()]);
    Mail::shouldReceive('to')->andThrow(new RuntimeException('Rendering failed near SECRET-00001-ABCD'));
    $job = new SendOrderPaidEmail($this->order->id);
    $job->tries = 1;
    dispatch($job);

    $this->artisan('queue:work', ['--once' => true, '--stop-when-empty' => true]);

    $failed = DB::table('failed_jobs')->sole();
    expect($failed->exception)->toContain(OrderPaidEmailFailed::class)
        ->and($failed->exception)->not->toContain('SECRET-00001-ABCD')
        ->and($failed->payload)->not->toContain('SECRET-00001-ABCD')
        ->and(File::get($log))->toContain('Payment confirmation email gave up')
        ->and(File::get($log))->not->toContain('SECRET-00001-ABCD');
    File::delete($log);
});

it('refuses to send through the log mailer, which would write the keys to the log', function () {
    Mail::fake();
    config(['mail.default' => 'log']);

    SendOrderPaidEmail::dispatchSync($this->order->id);

    Mail::assertNothingSent();
    expect(Order::query()->whereKey($this->order->id)->value('paid_email_sent_at'))->toBeNull();
});
