<?php

namespace App\Enums;

/**
 * Where a product stands for sale (docs/domain/catalog.md, "Product status").
 * Only published products are shown in the storefront.
 */
enum ProductStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Published => 'Publicado',
            self::Archived => 'Archivado',
        };
    }

    /**
     * Badge variant of the status in admin tables (shadcn badge variants).
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Published => 'default',
            self::Archived => 'outline',
        };
    }
}
