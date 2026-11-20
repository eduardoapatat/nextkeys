<?php

namespace App\Actions\Orders;

use App\Models\Order;
use RuntimeException;

/**
 * A payment arrived for an order that can no longer take it: it was cancelled,
 * or it expired and its stock is gone. The money must be refunded (the refund
 * policy is an open question in docs/domain/orders.md).
 */
final class OrderCannotBePaid extends RuntimeException
{
    public static function cancelled(Order $order): self
    {
        return new self("Order {$order->reference} was cancelled.");
    }

    public static function outOfStock(Order $order, InsufficientStock $previous): self
    {
        return new self("Order {$order->reference} expired and its stock is no longer available.", previous: $previous);
    }
}
