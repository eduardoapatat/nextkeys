<?php

namespace App\Actions\Catalog;

use App\Enums\ProductStatus;
use Illuminate\Http\UploadedFile;

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
     * @param  ?UploadedFile  $cover  A new cover; null keeps the current one unless $removeCover.
     * @param  bool  $coverBand  Whether the storefront frames the cover in its platform's case.
     */
    public function __construct(
        public int $titleId,
        public ?int $activationServiceId,
        public ?int $regionId,
        public array $platformIds,
        public int $price,
        public ?int $stock,
        public ProductStatus $status,
        public ?string $slug,
        public ?UploadedFile $cover = null,
        public bool $removeCover = false,
        public bool $coverBand = true,
    ) {}
}
