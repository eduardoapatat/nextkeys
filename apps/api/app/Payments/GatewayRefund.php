<?php

namespace App\Payments;

/**
 * A refund the gateway accepted.
 */
final readonly class GatewayRefund
{
    public function __construct(public RefundType $type) {}
}
