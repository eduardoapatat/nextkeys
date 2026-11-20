<?php

namespace App\Enums;

enum ProductKeyStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Sold = 'sold';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::Reserved => 'Reservada',
            self::Sold => 'Vendida',
        };
    }

    /**
     * Badge variant of the status in admin tables (shadcn badge variants).
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Available => 'default',
            self::Reserved => 'secondary',
            self::Sold => 'outline',
        };
    }
}
