<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\ProductKeyStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

final class MarkOrderPaid
{
    public function __construct(private LockPayableOrder $lockPayableOrder) {}

    /**
     * Records a confirmed payment: the reserved keys are sold, and an order
     * with nothing to ship is completed at once. Idempotent: a repeated
     * confirmation of a paid order changes nothing.
     *
     * An expired order is reopened first (its stock reserved again); if that
     * stock is gone, or the order was cancelled, nothing changes.
     *
     * @throws OrderCannotBePaid when the order is cancelled or expired without stock.
     * @throws OrderBusy
     */
    public function handle(Order $order): void
    {
        try {
            $this->lockPayableOrder->handle($order, function (Order $locked): void {
                DB::table('product_keys')
                    ->whereIn('order_item_id', $locked->items()->select('id'))
                    ->update(['status' => ProductKeyStatus::Sold, 'updated_at' => now()]);

                $locked->update(['status' => OrderStatus::Paid, 'paid_at' => now()]);

                if (! $locked->hasPhysicalItems()) {
                    $locked->update(['status' => OrderStatus::Completed, 'completed_at' => now()]);
                }
            });
        } catch (OrderCannotBePaid $exception) {
            if ($exception->reason !== OrderCannotBePaidReason::AlreadyPaid) {
                throw $exception;
            }
        }

        $order->refresh();
    }
}
