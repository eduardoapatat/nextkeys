<?php

namespace App\Actions\Orders;

use App\Enums\Carrier;
use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Jobs\SendOrderShippedEmail;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Records that a paid order's parcel left with a courier and queues the
 * shipping email (docs/domain/shipping.md). The order row is locked first,
 * so two admins cannot ship it twice.
 */
final class ShipOrder
{
    /**
     * @throws ValidationException
     */
    public function handle(Order $order, Carrier $carrier, string $trackingNumber): void
    {
        DB::transaction(function () use ($order, $carrier, $trackingNumber): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->sole();
            $shipment = $locked->shipment;

            if (! $shipment instanceof Shipment) {
                throw ValidationException::withMessages(['order' => 'Este pedido no tiene productos físicos que despachar.']);
            }

            if ($locked->status !== OrderStatus::Paid) {
                throw ValidationException::withMessages(['order' => 'Solo se despacha un pedido pagado.']);
            }

            if ($shipment->status !== ShipmentStatus::Pending) {
                throw ValidationException::withMessages(['order' => 'Este pedido ya fue despachado.']);
            }

            $shipment->forceFill([
                'status' => ShipmentStatus::Shipped,
                'carrier' => $carrier,
                'tracking_number' => trim($trackingNumber),
                'shipped_at' => now(),
            ])->save();

            SendOrderShippedEmail::dispatch($locked->id);
        });

        $order->refresh();
    }
}
