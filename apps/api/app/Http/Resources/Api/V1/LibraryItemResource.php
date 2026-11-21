<?php

namespace App\Http\Resources\Api\V1;

use App\Models\OrderItem;
use App\Models\ProductKey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A key line of a paid order in "My library". Keys go out as hints only;
 * the codes come from the reveal endpoint, one line at a time.
 *
 * Expects `order`, `keys` and `product.{platforms,region,activationService}`
 * loaded.
 *
 * @property OrderItem $resource
 */
class LibraryItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $item = $this->resource;
        $product = $item->product;

        return [
            'order_reference' => $item->order->reference,
            'paid_at' => $item->order->paid_at?->toIso8601String(),
            'title_name' => $item->title_name,
            'type' => ['slug' => $item->product_type->value, 'name' => $item->product_type->label()],
            'quantity' => $item->quantity,
            'product' => [
                'slug' => $product->slug,
                'platforms' => ReferenceResource::collection($product->platforms),
                'region' => $product->region === null ? null : new ReferenceResource($product->region),
                'activation_service' => $product->activationService === null ? null : new ReferenceResource($product->activationService),
            ],
            'keys' => $item->keys->map(fn (ProductKey $key): array => [
                'hint' => $key->code_hint,
                'revealed' => $key->revealed_at !== null,
            ])->values()->all(),
        ];
    }
}
