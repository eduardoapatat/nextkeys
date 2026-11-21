<?php

namespace App\Actions\Orders;

use App\Models\Order;
use RuntimeException;

/**
 * The order's row lock was not granted within the payment lock timeout:
 * another request is changing the order. Nothing was decided.
 */
final class OrderBusy extends RuntimeException
{
    public static function for(Order $order): self
    {
        return new self("Order {$order->reference} is locked by another request.");
    }
}
