<?php

namespace App\Enums;

/**
 * Who started a payment attempt, so the gateway's return goes back there
 * (docs/domain/payments.md, "Attempt origin").
 */
enum PaymentOrigin: string
{
    /** The customer's checkout in the Nuxt storefront. */
    case Storefront = 'storefront';

    /** The local-only "Pagar con Webpay (prueba)" button of the admin panel. */
    case Panel = 'panel';
}
