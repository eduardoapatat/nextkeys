<?php

namespace App\Payments;

/**
 * A transaction as the gateway reports it after a confirm or a status call.
 */
final readonly class GatewayPayment
{
    /**
     * @param  PaymentReceipt|null  $receipt  always present when Authorized.
     */
    public function __construct(
        public GatewayPaymentState $state,
        public string $buyOrder,
        public int $amount,
        public ?int $responseCode,
        public ?PaymentReceipt $receipt,
    ) {}

    /**
     * Charged, and nothing else: refunded states do not count (the official
     * SDK's `isApproved()` wrongly accepts them).
     *
     * @phpstan-assert-if-true PaymentReceipt $this->receipt
     */
    public function isApproved(): bool
    {
        return $this->state === GatewayPaymentState::Authorized
            && $this->responseCode === 0
            && $this->receipt !== null;
    }

    /**
     * Whether the gateway charged what the store asked for.
     */
    public function matches(string $buyOrder, int $amount): bool
    {
        return $this->buyOrder === $buyOrder && $this->amount === $amount;
    }
}
