<?php

namespace App\Actions\Catalog\Storefront;

use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class FindPublishedProduct
{
    /**
     * The published product with this slug, with `variants`: every published
     * product of its title, itself included, cheapest first. A draft or
     * archived product is not found, like an unknown slug.
     *
     * @throws ModelNotFoundException<Product>
     */
    public function handle(string $slug): Product
    {
        $product = Product::query()
            ->storefront()
            ->where('slug', $slug)
            ->firstOrFail();

        $variants = Product::query()
            ->storefront()
            ->where('title_id', $product->title_id)
            ->orderBy('price')
            ->orderBy('id')
            ->get();

        return $product->setRelation('variants', $variants);
    }
}
