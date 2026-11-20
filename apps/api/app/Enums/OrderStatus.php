<?php

namespace App\Enums;

/**
 * Where an order stands (docs/domain/orders.md, "Statuses"). The allowed
 * transitions are enforced by the `orders_status_transition` trigger.
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Paid => 'Pagado',
            self::Completed => 'Completado',
            self::Cancelled => 'Cancelado',
            self::Expired => 'Expirado',
        };
    }

    /**
     * Badge variant of the status in admin tables (shadcn badge variants).
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Pending => 'secondary',
            self::Paid, self::Completed => 'default',
            self::Cancelled, self::Expired => 'outline',
        };
    }
}
