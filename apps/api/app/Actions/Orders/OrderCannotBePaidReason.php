<?php

namespace App\Actions\Orders;

enum OrderCannotBePaidReason
{
    case AlreadyPaid;
    case Cancelled;
    case OutOfStock;
}
