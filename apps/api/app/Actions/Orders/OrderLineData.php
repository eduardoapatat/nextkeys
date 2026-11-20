<?php

namespace App\Actions\Orders;

/**
 * One line of a checkout: a product and how many units of it.
 */
final readonly class OrderLineData
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {}
}
