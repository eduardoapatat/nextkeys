<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

final class ExpireOrders
{
    private const int BATCH_SIZE = 100;

    public function __construct(private ReleaseOrderStock $releaseOrderStock) {}

    /**
     * Expires pending orders past `expires_at` and releases their stock, in
     * batches of one transaction each. Orders locked by a payment in progress
     * are skipped (SKIP LOCKED); the payment decides their fate.
     *
     * @return int How many orders expired.
     */
    public function handle(): int
    {
        $expired = 0;

        do {
            $batch = DB::transaction(function (): int {
                $orders = Order::query()
                    ->where('status', OrderStatus::Pending)
                    ->where('expires_at', '<', now())
                    ->orderBy('id')
                    ->limit(self::BATCH_SIZE)
                    ->lock('FOR UPDATE SKIP LOCKED')
                    ->get();

                foreach ($orders as $order) {
                    $this->releaseOrderStock->handle($order);
                    $order->update(['status' => OrderStatus::Expired]);
                }

                return $orders->count();
            });

            $expired += $batch;
        } while ($batch === self::BATCH_SIZE);

        return $expired;
    }
}
