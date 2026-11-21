<?php

namespace App\Payments\Transbank;

enum WebpayReturnKind
{
    /** Only `token_ws`: the customer paid or the bank declined; commit to know which. */
    case Paid;

    /** `TBK_TOKEN` + `TBK_ORDEN_COMPRA`, with or without `token_ws`: cancelled in Webpay or back from a Webpay error page. Never committed. */
    case Cancelled;

    /** Only `TBK_ORDEN_COMPRA`: the payment form timed out. */
    case TimedOut;

    /** Anything else, including a malformed token. */
    case Invalid;
}
