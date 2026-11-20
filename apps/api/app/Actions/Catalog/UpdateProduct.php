<?php

namespace App\Actions\Catalog;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

final class UpdateProduct
{
    public function __construct(private readonly SaveProduct $saveProduct) {}

    /**
     * Updates a product and its platforms. Its type never changes, and an
     * empty slug keeps the current one.
     *
     * @throws ValidationException
     */
    public function handle(Product $product, ProductData $data): Product
    {
        return $this->saveProduct->handle($product, $product->type, $data);
    }
}
