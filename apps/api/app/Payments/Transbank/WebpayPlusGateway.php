<?php

namespace App\Payments\Transbank;

use App\Payments\GatewayPayment;
use App\Payments\GatewayRefund;
use App\Payments\PaymentGateway;
use App\Payments\PaymentGatewayException;
use App\Payments\PaymentRedirect;
use Closure;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use SensitiveParameter;

/**
 * Transbank Webpay Plus REST API v1.2 through Laravel's HTTP client
 * (docs/domain/payments.md, "Webpay Plus API").
 */
final readonly class WebpayPlusGateway implements PaymentGateway
{
    private const string BASE_PATH = '/rswebpaytransaction/api/webpay/v1.2/transactions';

    private const string TOKEN_PATTERN = '/^[0-9a-f]{64}$/';

    public function __construct(
        private string $host,
        private string $commerceCode,
        #[SensitiveParameter] private string $apiKey,
        private int $connectTimeoutSeconds,
        private int $timeoutSeconds,
        private int $commitRetries,
    ) {}

    public function start(string $buyOrder, string $sessionId, int $amount, string $returnUrl): PaymentRedirect
    {
        $body = $this->send('create', fn (PendingRequest $request): Response => $request->post(self::BASE_PATH, [
            'buy_order' => $buyOrder,
            'session_id' => $sessionId,
            'amount' => $amount,
            'return_url' => $returnUrl,
        ]));

        $token = $body->string('token');

        if (preg_match(self::TOKEN_PATTERN, $token) !== 1) {
            throw PaymentGatewayException::malformed('create', 'the token has an invalid format');
        }

        return new PaymentRedirect(url: $body->string('url'), token: $token);
    }

    public function confirm(string $token): GatewayPayment
    {
        $path = $this->transactionPath('commit', $token);

        return $this->send('commit', fn (PendingRequest $request): Response => $request->put($path), $this->commitRetries)
            ->toPayment();
    }

    public function status(string $token): GatewayPayment
    {
        $path = $this->transactionPath('status', $token);

        return $this->send('status', fn (PendingRequest $request): Response => $request->get($path))->toPayment();
    }

    public function refund(string $token, int $amount): GatewayRefund
    {
        $path = $this->transactionPath('refund', $token).'/refunds';

        return $this->send('refund', fn (PendingRequest $request): Response => $request->post($path, ['amount' => $amount]))
            ->toRefund();
    }

    /**
     * Validates the token before it goes into a URL.
     *
     * @throws PaymentGatewayException
     */
    private function transactionPath(string $operation, string $token): string
    {
        if (preg_match(self::TOKEN_PATTERN, $token) !== 1) {
            throw PaymentGatewayException::invalidToken($operation);
        }

        return self::BASE_PATH.'/'.$token;
    }

    /**
     * Sends one request and turns every failure into a PaymentGatewayException
     * that holds no part of the request.
     *
     * @param  Closure(PendingRequest): Response  $call
     * @param  int  $connectionRetries  only for idempotent calls: a request
     *                                  that got no response may still have
     *                                  reached the gateway.
     *
     * @throws PaymentGatewayException
     */
    private function send(string $operation, Closure $call, int $connectionRetries = 0): WebpayResponse
    {
        $response = null;

        for ($try = 0; $response === null; $try++) {
            try {
                $response = $call($this->request());
            } catch (ConnectionException|TransferException) {
                if ($try >= $connectionRetries) {
                    throw PaymentGatewayException::unreachable($operation);
                }
            }
        }

        $body = $response->json();

        if (! $response->successful()) {
            $message = is_array($body) && is_string($body['error_message'] ?? null) ? $body['error_message'] : null;

            throw PaymentGatewayException::httpError($operation, $response->status(), $message);
        }

        if (! is_array($body)) {
            throw PaymentGatewayException::malformed($operation, 'the body is not a JSON object');
        }

        return new WebpayResponse($operation, $body);
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->host)
            ->connectTimeout($this->connectTimeoutSeconds)
            ->timeout($this->timeoutSeconds)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'Tbk-Api-Key-Id' => $this->commerceCode,
                'Tbk-Api-Key-Secret' => $this->apiKey,
            ]);
    }
}
