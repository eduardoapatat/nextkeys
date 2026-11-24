<?php

namespace App\Enums;

/**
 * Where an order's parcel is (docs/domain/shipping.md). The
 * `shipments_status_transition` trigger allows `pending` → `shipped` (only
 * for a paid order) → `delivered`.
 */
enum ShipmentStatus: string
{
    case Pending = 'pending';
    case Shipped = 'shipped';
    case Delivered = 'delivered';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Por despachar',
            self::Shipped => 'Despachado',
            self::Delivered => 'Entregado',
        };
    }

    /**
     * Badge variant of the status in admin tables (shadcn badge variants).
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Pending => 'outline',
            self::Shipped => 'secondary',
            self::Delivered => 'default',
        };
    }
}
