<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * The product page: the listing fields, the title's description and every
 * published variant of the title (loaded by FindPublishedProduct).
 */
class ProductDetailResource extends ProductResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Collection<int, Product> $variants */
        $variants = $this->resource->getRelation('variants');

        return [
            ...parent::toArray($request),
            'variants' => ProductResource::collection($variants),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function title(): array
    {
        return [
            'name' => $this->resource->title->name,
            'description' => $this->resource->title->description,
        ];
    }
}
