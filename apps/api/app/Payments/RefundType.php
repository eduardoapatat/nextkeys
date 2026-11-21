<?php

namespace App\Payments;

enum RefundType: string
{
    /** The charge was undone before settlement (Webpay: within 3 hours). */
    case Reversed = 'reversed';

    /** The charge was annulled after settlement. */
    case Nullified = 'nullified';
}
