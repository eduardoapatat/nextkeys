<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\Config;

final class ReopenExpiredOrder
{
    public function __construct(private ReserveOrderStock $reserveOrderStock) {}

    /**
     * Reserves the stock of an expired order again and moves it back to
     * `pending` with a fresh hold. The caller holds the order's row lock in a
     * transaction, and rolls it back when this throws.
     *
     * @throws OrderCannotBePaid when the stock is gone.
     */
    public function handle(Order $lockedOrder): void
    {
        try {
            $this->reserveOrderStock->handle($lockedOrder);
        } catch (InsufficientStock $exception) {
            throw OrderCannotBePaid::outOfStock($lockedOrder, $exception);
        }

        $lockedOrder->update([
            'status' => OrderStatus::Pending,
            'expires_at' => now()->addMinutes(Config::integer('orders.reservation_minutes')),
        ]);
    }
}
