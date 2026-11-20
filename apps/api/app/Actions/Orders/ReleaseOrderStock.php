<?php

namespace App\Actions\Orders;

use App\Enums\ProductKeyStatus;
use App\Enums\ProductType;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Gives back what a pending order holds: its reserved keys become available
 * again and its boxes return to stock. Runs inside the caller's transaction,
 * with the order row locked.
 */
final class ReleaseOrderStock
{
    public function handle(Order $order): void
    {
        $items = $order->items()->orderBy('product_id')->get();

        DB::table('product_keys')
            ->whereIn('order_item_id', $items->modelKeys())
            ->where('status', ProductKeyStatus::Reserved)
            ->update([
                'status' => ProductKeyStatus::Available,
                'order_item_id' => null,
                'updated_at' => now(),
            ]);

        foreach ($items->where('product_type', ProductType::Physical) as $item) {
            DB::update(
                'UPDATE products SET stock = stock + ?, updated_at = now() WHERE id = ?',
                [$item->quantity, $item->product_id],
            );
        }
    }
}
