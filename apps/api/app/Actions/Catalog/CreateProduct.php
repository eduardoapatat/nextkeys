<?php

namespace App\Actions\Catalog;

use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

final class CreateProduct
{
    public function __construct(private readonly SaveProduct $saveProduct) {}

    /**
     * Creates a product with its platforms. An empty slug is generated from
     * the product's identity, with a numeric suffix if it is taken.
     *
     * @throws ValidationException
     */
    public function handle(ProductType $type, ProductData $data): Product
    {
        return $this->saveProduct->handle(new Product, $type, $data);
    }
}
