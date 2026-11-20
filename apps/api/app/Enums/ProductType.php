<?php

namespace App\Enums;

enum ProductType: string
{
    case DigitalKey = 'digital_key';
    case License = 'license';
    case Physical = 'physical';

    public function label(): string
    {
        return match ($this) {
            self::DigitalKey => 'Key digital',
            self::License => 'Licencia',
            self::Physical => 'Físico',
        };
    }

    /**
     * Badge variant of the type in admin tables (shadcn badge variants).
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::DigitalKey => 'default',
            self::License => 'secondary',
            self::Physical => 'outline',
        };
    }

    /**
     * Whether the product is delivered as a code from `product_keys`
     * (its stock is the count of available keys) instead of being shipped.
     */
    public function isDeliveredByKey(): bool
    {
        return match ($this) {
            self::DigitalKey, self::License => true,
            self::Physical => false,
        };
    }
}
