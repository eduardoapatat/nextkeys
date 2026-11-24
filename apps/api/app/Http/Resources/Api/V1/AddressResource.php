<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A saved shipping address, identified by its `public_id` (never the id).
 *
 * @property-read CustomerAddress $resource
 */
class AddressResource extends JsonResource
{
    /**
     * @return array{public_id: string, recipient_name: string, phone: string, region: array{code: string, name: string}, commune: string, street: string, street_number: string, apartment: string|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->resource->public_id,
            'recipient_name' => $this->resource->recipient_name,
            'phone' => $this->resource->phone,
            'region' => ['code' => $this->resource->region->value, 'name' => $this->resource->region->label()],
            'commune' => $this->resource->commune,
            'street' => $this->resource->street,
            'street_number' => $this->resource->street_number,
            'apartment' => $this->resource->apartment,
        ];
    }
}
