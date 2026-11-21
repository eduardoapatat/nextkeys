<?php

namespace App\Payments;

enum GatewayPaymentState
{
    /** Created; paid or not, it was never confirmed (or not yet). */
    case Initialized;

    /** Confirmed: the customer was charged. */
    case Authorized;

    /** The bank declined it. */
    case Failed;

    /** Refunded in full (reversal). */
    case Reversed;

    /** Refunded in full (annulment). */
    case Nullified;

    /** Partly refunded. */
    case PartiallyNullified;
}
