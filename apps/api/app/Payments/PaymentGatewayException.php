<?php

namespace App\Payments;

use RuntimeException;

/**
 * The only exception a PaymentGateway throws. It never holds the failed
 * request, its URL or a previous exception: the HTTP client's exceptions
 * carry the request headers, credentials included, and would leak them to
 * any log that records the exception.
 */
final class PaymentGatewayException extends RuntimeException
{
    private const int MAX_GATEWAY_MESSAGE_LENGTH = 200;

    private function __construct(string $message, public readonly ?int $httpStatus = null)
    {
        parent::__construct($message);
    }

    public static function httpError(string $operation, int $httpStatus, ?string $gatewayMessage): self
    {
        $detail = $gatewayMessage === null
            ? ''
            : ': '.mb_substr($gatewayMessage, 0, self::MAX_GATEWAY_MESSAGE_LENGTH);

        return new self("Payment gateway {$operation} failed with HTTP {$httpStatus}{$detail}", $httpStatus);
    }

    public static function unreachable(string $operation): self
    {
        return new self("Payment gateway {$operation} failed: the gateway could not be reached.");
    }

    public static function malformed(string $operation, string $detail): self
    {
        return new self("Payment gateway {$operation} returned an invalid response: {$detail}.");
    }

    public static function invalidToken(string $operation): self
    {
        return new self("Payment gateway {$operation} refused: the token has an invalid format.");
    }

    /**
     * The gateway understood the request and refused it by its rules (e.g.
     * "already reversed"); retrying will not help.
     */
    public function isBusinessRule(): bool
    {
        return $this->httpStatus === 422;
    }
}
