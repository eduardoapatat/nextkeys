<?php

namespace App\Payments;

/**
 * Where to send the customer to pay: an HTML form that POSTs `token_ws` to
 * `url` (never in an iframe).
 */
final readonly class PaymentRedirect
{
    public function __construct(
        public string $url,
        public string $token,
    ) {}
}
