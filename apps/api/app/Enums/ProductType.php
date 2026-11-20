<?php

namespace App\Enums;

enum ProductType: string
{
    case DigitalKey = 'digital_key';
    case License = 'license';
    case Physical = 'physical';

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
