<?php

namespace App\Payments;

/**
 * A card payment provider whose charge is only confirmed when the store
 * confirms it (Transbank Webpay Plus' commit). The domain only talks to this
 * interface; no provider type leaks out of its implementation.
 *
 * Every method throws PaymentGatewayException on any failure (network,
 * HTTP error, malformed response) and nothing else.
 */
interface PaymentGateway
{
    /**
     * Opens a transaction for `$amount` CLP; the customer is sent to the
     * returned URL with the token, and comes back to `$returnUrl`.
     *
     * @throws PaymentGatewayException
     */
    public function start(string $buyOrder, string $sessionId, int $amount, string $returnUrl): PaymentRedirect;

    /**
     * Confirms (commits) the payment the customer made. Idempotent: a
     * repeated call returns the same result.
     *
     * @throws PaymentGatewayException
     */
    public function confirm(string $token): GatewayPayment;

    /**
     * Reads the transaction without changing it.
     *
     * @throws PaymentGatewayException
     */
    public function status(string $token): GatewayPayment;

    /**
     * Gives `$amount` CLP of a confirmed payment back to the customer.
     *
     * @throws PaymentGatewayException
     */
    public function refund(string $token, int $amount): GatewayRefund;
}
