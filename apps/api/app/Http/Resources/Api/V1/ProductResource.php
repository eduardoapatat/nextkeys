<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A product as the storefront listing shows it. An explicit allowlist: stock,
 * status, ids and anything about keys never leave the API.
 *
 * @property-read Product $resource
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product = $this->resource;

        return [
            'slug' => $product->slug,
            'type' => $product->type->value,
            'price' => $product->price,
            'in_stock' => (bool) $product->in_stock,
            'cover_url' => $product->coverUrl(),
            'title' => $this->title(),
            'platforms' => ReferenceResource::collection($product->platforms),
            'region' => $product->region === null ? null : new ReferenceResource($product->region),
            'activation_service' => $product->activationService === null ? null : new ReferenceResource($product->activationService),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function title(): array
    {
        return ['name' => $this->resource->title->name];
    }
}
