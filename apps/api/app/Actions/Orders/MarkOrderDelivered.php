<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Records that a shipped parcel reached the customer: the shipment becomes
 * `delivered` and the order `completed` in the same transaction (the order
 * trigger rejects completing it before). The withdrawal period starts here.
 */
final class MarkOrderDelivered
{
    /**
     * @throws ValidationException
     */
    public function handle(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->sole();
            $shipment = $locked->shipment;

            if (! $shipment instanceof Shipment || $locked->status !== OrderStatus::Paid || $shipment->status !== ShipmentStatus::Shipped) {
                throw ValidationException::withMessages(['order' => 'Solo se marca como entregado un pedido despachado.']);
            }

            $shipment->forceFill(['status' => ShipmentStatus::Delivered, 'delivered_at' => now()])->save();
            $locked->update(['status' => OrderStatus::Completed, 'completed_at' => now()]);
        });

        $order->refresh();
    }
}
