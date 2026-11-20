<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CancelOrder
{
    public function __construct(private ReleaseOrderStock $releaseOrderStock) {}

    /**
     * Cancels a pending order and releases its stock. The order row is locked
     * first, so a payment or the expiry cannot act on it at the same time.
     *
     * @throws ValidationException
     */
    public function handle(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->sole();

            if ($locked->status !== OrderStatus::Pending) {
                throw ValidationException::withMessages([
                    'order' => 'Solo se puede cancelar un pedido pendiente.',
                ]);
            }

            $this->releaseOrderStock->handle($locked);

            $locked->update(['status' => OrderStatus::Cancelled, 'cancelled_at' => now()]);
        });

        $order->refresh();
    }
}
