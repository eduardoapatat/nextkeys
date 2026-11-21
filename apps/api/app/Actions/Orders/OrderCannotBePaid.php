<?php

namespace App\Actions\Orders;

use App\Models\Order;
use RuntimeException;

/**
 * The order cannot take a payment: it is already paid, it was cancelled, or
 * it expired and its stock is gone. A gateway must not charge it, and a
 * charge already made is refunded (docs/domain/payments.md).
 */
final class OrderCannotBePaid extends RuntimeException
{
    private function __construct(public readonly OrderCannotBePaidReason $reason, string $message, ?InsufficientStock $previous = null)
    {
        parent::__construct($message, previous: $previous);
    }

    public static function alreadyPaid(Order $order): self
    {
        return new self(OrderCannotBePaidReason::AlreadyPaid, "Order {$order->reference} is already paid.");
    }

    public static function cancelled(Order $order): self
    {
        return new self(OrderCannotBePaidReason::Cancelled, "Order {$order->reference} was cancelled.");
    }

    public static function outOfStock(Order $order, InsufficientStock $previous): self
    {
        return new self(OrderCannotBePaidReason::OutOfStock, "Order {$order->reference} expired and its stock is no longer available.", $previous);
    }
}
