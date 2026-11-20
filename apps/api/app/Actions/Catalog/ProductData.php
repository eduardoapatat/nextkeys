<?php

namespace App\Actions\Catalog;

/**
 * The editable fields of a product, as the create and update Actions receive
 * them. It has no `type`: the type is chosen once, as a separate argument of
 * CreateProduct, and can never be changed (docs/domain/catalog.md).
 */
final readonly class ProductData
{
    /**
     * @param  list<int>  $platformIds
     * @param  ?string  $slug  Null keeps the current slug on update and generates one on create.
     */
    public function __construct(
        public int $titleId,
        public ?int $activationServiceId,
        public ?int $regionId,
        public array $platformIds,
        public int $price,
        public ?int $stock,
        public bool $isPublished,
        public ?string $slug,
    ) {}
}
