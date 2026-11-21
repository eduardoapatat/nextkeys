<?php

namespace App\Enums;

/**
 * Where a payment attempt stands (docs/domain/payments.md, "Design"). Only
 * `initialized` can change, enforced by the `payment_attempts_status_transition`
 * trigger.
 */
enum PaymentAttemptStatus: string
{
    /** Sent to the gateway; the customer may be paying. */
    case Initialized = 'initialized';

    /** Charged and recorded on the order. */
    case Authorized = 'authorized';

    /** The bank declined it. */
    case Rejected = 'rejected';

    /** The customer cancelled it in the gateway. */
    case Cancelled = 'cancelled';

    /** Never charged: the store did not commit it, or it never reached the gateway. */
    case Aborted = 'aborted';

    /** Charged and refunded automatically, because the order could not take it. */
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Initialized => 'Iniciado',
            self::Authorized => 'Autorizado',
            self::Rejected => 'Rechazado',
            self::Cancelled => 'Anulado por el cliente',
            self::Aborted => 'Sin cobro',
            self::Refunded => 'Reembolsado',
        };
    }

    /**
     * Badge variant of the status in the panel (shadcn badge variants).
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Initialized => 'secondary',
            self::Authorized => 'default',
            self::Rejected, self::Refunded => 'destructive',
            self::Cancelled, self::Aborted => 'outline',
        };
    }
}
