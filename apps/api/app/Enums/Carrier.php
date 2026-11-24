<?php

namespace App\Enums;

/**
 * The courier that carries a shipment (docs/domain/shipping.md). The
 * `shipments_carrier_check` CHECK lists the same values.
 */
enum Carrier: string
{
    case Starken = 'starken';
    case Chilexpress = 'chilexpress';
    case BlueExpress = 'blue_express';
    case CorreosChile = 'correos_chile';

    public function label(): string
    {
        return match ($this) {
            self::Starken => 'Starken',
            self::Chilexpress => 'Chilexpress',
            self::BlueExpress => 'Blue Express',
            self::CorreosChile => 'Correos de Chile',
        };
    }

    /**
     * The courier's public tracking page for a tracking number.
     */
    public function trackingUrl(string $trackingNumber): string
    {
        $number = rawurlencode($trackingNumber);

        return match ($this) {
            self::Starken => "https://www.starken.cl/seguimiento?codigo={$number}",
            self::Chilexpress => "https://centrodeayuda.chilexpress.cl/seguimiento/{$number}",
            self::BlueExpress => "https://www.blue.cl/seguimiento/?n_seguimiento={$number}",
            self::CorreosChile => "https://www.correos.cl/web/guest/seguimiento-en-linea?codigos={$number}",
        };
    }
}
