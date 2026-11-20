<?php

namespace App\Actions\Orders;

use App\Models\OrderItem;
use RuntimeException;

/**
 * A line could not hold its quantity: too few available keys or boxes.
 */
final class InsufficientStock extends RuntimeException
{
    public function __construct(public readonly OrderItem $item)
    {
        parent::__construct("Not enough stock for order item {$item->id}.");
    }
}
