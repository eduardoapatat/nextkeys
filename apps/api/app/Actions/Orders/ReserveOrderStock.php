<?php

namespace App\Actions\Orders;

use App\Enums\ProductKeyStatus;
use App\Enums\ProductType;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

/**
 * Holds the stock of every line of an order: available keys become reserved
 * for the line, and physical stock decreases. All or nothing: it must run in
 * a transaction, which the caller rolls back on InsufficientStock.
 */
final class ReserveOrderStock
{
    /**
     * Lines are reserved in product order, so concurrent checkouts lock rows
     * in the same order and cannot deadlock. Keys are taken with SKIP LOCKED:
     * a checkout never waits for keys another one is reserving.
     *
     * @throws InsufficientStock
     */
    public function handle(Order $order): void
    {
        $items = $order->items()->orderBy('product_id')->get();

        foreach ($items as $item) {
            $reserved = $item->product_type === ProductType::Physical
                ? $this->reserveBoxes($item)
                : $this->reserveKeys($item);

            if (! $reserved) {
                throw new InsufficientStock($item);
            }
        }
    }

    private function reserveKeys(OrderItem $item): bool
    {
        $reserved = DB::update(<<<'SQL'
            UPDATE product_keys
            SET status = ?, order_item_id = ?, updated_at = now()
            WHERE id IN (
                SELECT id FROM product_keys
                WHERE product_id = ? AND status = ?
                ORDER BY id
                LIMIT ?
                FOR UPDATE SKIP LOCKED
            )
            SQL, [
            ProductKeyStatus::Reserved->value,
            $item->id,
            $item->product_id,
            ProductKeyStatus::Available->value,
            $item->quantity,
        ]);

        return $reserved === $item->quantity;
    }

    private function reserveBoxes(OrderItem $item): bool
    {
        return DB::update(
            'UPDATE products SET stock = stock - ?, updated_at = now() WHERE id = ? AND stock >= ?',
            [$item->quantity, $item->product_id, $item->quantity],
        ) === 1;
    }
}
