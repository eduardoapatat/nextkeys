<?php

use App\Payments\GatewayPaymentState;
use App\Payments\PaymentGateway;
use App\Payments\PaymentGatewayException;
use App\Payments\RefundType;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
    $this->gateway = app(PaymentGateway::class);
    $this->token = webpayToken();
});

/**
 * Runs a gateway call that must fail, and returns its exception.
 */
function gatewayFailure(Closure $call): PaymentGatewayException
{
    try {
        $call();
    } catch (PaymentGatewayException $exception) {
        return $exception;
    }

    test()->fail('Expected a PaymentGatewayException.');
}

it('creates a transaction with the store credentials', function () {
    Http::fake([webpayUrl() => Http::response([
        'token' => $this->token,
        'url' => 'https://webpay3gint.transbank.cl/webpayserver/initTransaction',
    ])]);

    $redirect = $this->gateway->start('NK-7QF2K9-1', 'NK-7QF2K9', 19990, 'https://api.test/payments/webpay/return');

    expect($redirect->token)->toBe($this->token)
        ->and($redirect->url)->toBe('https://webpay3gint.transbank.cl/webpayserver/initTransaction');
    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === webpayUrl()
        && $request->header('Tbk-Api-Key-Id') === ['597055555532']
        && $request->header('Tbk-Api-Key-Secret') === ['testing-transbank-secret']
        && $request->data() === [
            'buy_order' => 'NK-7QF2K9-1',
            'session_id' => 'NK-7QF2K9',
            'amount' => 19990,
            'return_url' => 'https://api.test/payments/webpay/return',
        ]);
});

it('rejects a created token with an invalid format', function () {
    Http::fake([webpayUrl() => Http::response(['token' => 'not-a-token', 'url' => 'https://webpay.test'])]);

    expect(fn () => $this->gateway->start('NK-7QF2K9-1', 'NK-7QF2K9', 19990, 'https://api.test/return'))
        ->toThrow(PaymentGatewayException::class, 'invalid response');
});

it('commits a payment and reads its receipt', function () {
    Http::fake([webpayUrl($this->token) => Http::response(webpayTransaction([
        'payment_type_code' => 'VC',
        'installments_number' => 3,
        'installments_amount' => 6663,
    ]))]);

    $payment = $this->gateway->confirm($this->token);

    expect($payment->isApproved())->toBeTrue()
        ->and($payment->matches('NK-7QF2K9-1', 19990))->toBeTrue()
        ->and($payment->receipt?->authorizationCode)->toBe('1213')
        ->and($payment->receipt?->paymentTypeCode)->toBe('VC')
        ->and($payment->receipt?->installmentsNumber)->toBe(3)
        ->and($payment->receipt?->installmentsAmount)->toBe(6663)
        ->and($payment->receipt?->cardLastDigits)->toBe('6623')
        ->and($payment->receipt?->accountingDate)->toBe('1120')
        ->and($payment->receipt?->transactionDate->toIso8601ZuluString())->toBe('2026-11-20T15:30:12Z');
    Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT' && $request->url() === webpayUrl($this->token));
});

it('approves only an authorized payment with response code 0', function (array $overrides, GatewayPaymentState $state) {
    Http::fake([webpayUrl($this->token) => Http::response(webpayTransaction($overrides))]);

    $payment = $this->gateway->confirm($this->token);

    expect($payment->state)->toBe($state)
        ->and($payment->isApproved())->toBeFalse();
})->with([
    'declined' => [['status' => 'FAILED', 'response_code' => -1], GatewayPaymentState::Failed],
    'reversed' => [['status' => 'REVERSED'], GatewayPaymentState::Reversed],
    'nullified' => [['status' => 'NULLIFIED'], GatewayPaymentState::Nullified],
    'authorized with a non-zero code' => [['response_code' => -1], GatewayPaymentState::Authorized],
]);

it('reads a declined payment without a receipt', function () {
    Http::fake([webpayUrl($this->token) => Http::response(webpayTransaction([
        'status' => 'FAILED',
        'response_code' => -1,
        'authorization_code' => null,
        'card_detail' => null,
    ]))]);

    $payment = $this->gateway->confirm($this->token);

    expect($payment->state)->toBe(GatewayPaymentState::Failed)
        ->and($payment->receipt)->toBeNull();
});

it('reads the status of a transaction never committed', function () {
    Http::fake([webpayUrl($this->token) => Http::response([
        'vci' => 'TSY',
        'amount' => 19990,
        'status' => 'INITIALIZED',
        'buy_order' => 'NK-7QF2K9-1',
        'session_id' => 'NK-7QF2K9',
        'card_detail' => ['card_number' => '6623'],
    ])]);

    $payment = $this->gateway->status($this->token);

    expect($payment->state)->toBe(GatewayPaymentState::Initialized)
        ->and($payment->responseCode)->toBeNull();
    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET' && $request->url() === webpayUrl($this->token));
});

it('refunds a payment', function (array $body, RefundType $type) {
    Http::fake([webpayUrl($this->token, '/refunds') => Http::response($body)]);

    expect($this->gateway->refund($this->token, 19990)->type)->toBe($type);
    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === webpayUrl($this->token, '/refunds')
        && $request->data() === ['amount' => 19990]);
})->with([
    'reversal' => [['type' => 'REVERSED'], RefundType::Reversed],
    'annulment' => [['type' => 'NULLIFIED', 'response_code' => 0, 'authorization_code' => '123456', 'nullified_amount' => 19990, 'balance' => 0], RefundType::Nullified],
]);

it('rejects an annulment that was not approved', function () {
    Http::fake([webpayUrl($this->token, '/refunds') => Http::response(['type' => 'NULLIFIED', 'response_code' => -1])]);

    expect(fn () => $this->gateway->refund($this->token, 19990))->toThrow(PaymentGatewayException::class);
});

it('never puts an invalid token into a URL', function (string $token) {
    Http::fake();

    expect(fn () => $this->gateway->confirm($token))->toThrow(PaymentGatewayException::class, 'invalid format')
        ->and(fn () => $this->gateway->status($token))->toThrow(PaymentGatewayException::class, 'invalid format')
        ->and(fn () => $this->gateway->refund($token, 1000))->toThrow(PaymentGatewayException::class, 'invalid format');
    Http::assertNothingSent();
})->with([
    'too short' => [str_repeat('a', 63)],
    'uppercase' => [strtoupper(webpayToken())],
    'path traversal' => ['../'.str_repeat('a', 61)],
]);

it('turns every failure into one exception without the credentials', function (Closure $response, ?int $httpStatus) {
    Http::fake([webpayUrl($this->token) => $response]);

    $exception = gatewayFailure(fn () => $this->gateway->status($this->token));

    expect($exception->httpStatus)->toBe($httpStatus)
        ->and($exception->getPrevious())->toBeNull()
        ->and((string) $exception)->not->toContain('testing-transbank-secret')
        ->and((string) $exception)->not->toContain($this->token)
        ->and(serialize($exception))->not->toContain('testing-transbank-secret');
})->with([
    'bad credentials' => [fn () => Http::response(['error_message' => 'Not Authorized'], 401), 401],
    'not found' => [fn () => Http::response(['error_message' => 'Transaction not found'], 404), 404],
    'business rule' => [fn () => Http::response(['error_message' => 'Invalid status'], 422), 422],
    'server error without a body' => [fn () => Http::response('', 500), 500],
    'network error' => [fn () => Http::failedConnection(), null],
    'invalid JSON' => [fn () => Http::response('<html>', 200), null],
    'missing field' => [fn () => Http::response(webpayTransaction(['amount' => null])), null],
    'mistyped field' => [fn () => Http::response(webpayTransaction(['amount' => '19990'])), null],
    'unknown status' => [fn () => Http::response(webpayTransaction(['status' => 'CAPTURED'])), null],
    'authorized without a receipt' => [fn () => Http::response(webpayTransaction(['authorization_code' => null])), null],
]);

it('keeps the gateway message of a refused request', function () {
    Http::fake([webpayUrl($this->token, '/refunds') => Http::response(['error_message' => 'Transaction already REVERSED'], 422)]);

    $exception = gatewayFailure(fn () => $this->gateway->refund($this->token, 19990));

    expect($exception->isBusinessRule())->toBeTrue()
        ->and($exception->getMessage())->toContain('Transaction already REVERSED');
});

it('retries a commit once after a network error, since commit is idempotent', function () {
    Http::fake([webpayUrl($this->token) => Http::sequence()
        ->pushFailedConnection()
        ->push(webpayTransaction())]);

    expect($this->gateway->confirm($this->token)->isApproved())->toBeTrue();
    Http::assertSentCount(2);
});

it('gives up a commit after its retry', function () {
    Http::fake([webpayUrl($this->token) => Http::failedConnection()]);

    expect(fn () => $this->gateway->confirm($this->token))->toThrow(PaymentGatewayException::class, 'could not be reached');
    Http::assertSentCount(2);
});

it('does not retry a commit the gateway answered', function () {
    Http::fake([webpayUrl($this->token) => Http::response(['error_message' => 'Transaction has an invalid finished state: aborted'], 422)]);

    expect(fn () => $this->gateway->confirm($this->token))->toThrow(PaymentGatewayException::class);
    Http::assertSentCount(1);
});

it('does not retry other calls after a network error', function () {
    Http::fake([webpayUrl($this->token) => Http::failedConnection()]);

    expect(fn () => $this->gateway->status($this->token))->toThrow(PaymentGatewayException::class);
    Http::assertSentCount(1);
});
