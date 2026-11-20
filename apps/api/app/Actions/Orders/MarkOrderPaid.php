<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\ProductKeyStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

final class MarkOrderPaid
{
    public function __construct(private ReserveOrderStock $reserveOrderStock) {}

    /**
     * Records a confirmed payment: the reserved keys are sold, and an order
     * with nothing to ship is completed at once. Idempotent: a repeated
     * confirmation of a paid order changes nothing.
     *
     * A payment for an expired order reserves its stock again; if that stock
     * is gone, or the order was cancelled, nothing changes and the money must
     * be refunded.
     *
     * @throws OrderCannotBePaid
     */
    public function handle(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->sole();

            if (in_array($locked->status, [OrderStatus::Paid, OrderStatus::Completed], true)) {
                return;
            }

            if ($locked->status === OrderStatus::Cancelled) {
                throw OrderCannotBePaid::cancelled($locked);
            }

            if ($locked->status === OrderStatus::Expired) {
                try {
                    $this->reserveOrderStock->handle($locked);
                } catch (InsufficientStock $exception) {
                    throw OrderCannotBePaid::outOfStock($locked, $exception);
                }
            }

            DB::table('product_keys')
                ->whereIn('order_item_id', $locked->items()->select('id'))
                ->update(['status' => ProductKeyStatus::Sold, 'updated_at' => now()]);

            $locked->update(['status' => OrderStatus::Paid, 'paid_at' => now()]);

            if (! $locked->hasPhysicalItems()) {
                $locked->update(['status' => OrderStatus::Completed, 'completed_at' => now()]);
            }
        });

        $order->refresh();
    }
}
