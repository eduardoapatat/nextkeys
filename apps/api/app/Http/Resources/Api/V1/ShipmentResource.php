<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An order's parcel as its customer sees it: the address it goes to, where
 * it is, how to track it and, once delivered, the last day to withdraw.
 *
 * @property-read Shipment $resource
 */
class ShipmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $shipment = $this->resource;

        return [
            'status' => ['slug' => $shipment->status->value, 'name' => $shipment->status->label()],
            'address' => [
                'recipient_name' => $shipment->recipient_name,
                'phone' => $shipment->phone,
                'region' => ['code' => $shipment->region->value, 'name' => $shipment->region->label()],
                'commune' => $shipment->commune,
                'street' => $shipment->street,
                'street_number' => $shipment->street_number,
                'apartment' => $shipment->apartment,
            ],
            'carrier' => $shipment->carrier === null ? null : ['slug' => $shipment->carrier->value, 'name' => $shipment->carrier->label()],
            'tracking_number' => $shipment->tracking_number,
            'tracking_url' => $shipment->trackingUrl(),
            'shipped_at' => $shipment->shipped_at?->toIso8601String(),
            'delivered_at' => $shipment->delivered_at?->toIso8601String(),
            'returnable_until' => $shipment->returnableUntil()?->toIso8601String(),
        ];
    }
}
