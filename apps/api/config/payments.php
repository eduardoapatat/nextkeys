<?php

return [

    /*
    | Payment attempts allowed per order. The `payment_attempts_number_range`
    | CHECK enforces the same limit. Mastercard blocks the 8th rejected retry
    | of a card within 24 hours, so one order stays well under it.
    */
    'max_attempts' => 5,

    /*
    | Starting an attempt extends the order's hold to at least this many
    | minutes from now: the Webpay form (4 minutes in production, 10 in
    | integration) plus the commit, so the order rarely expires mid-payment.
    */
    'payment_window_minutes' => 11,

    /*
    | `payments:reconcile` only looks at attempts older than this, so it never
    | races a customer who is still in the Webpay form or coming back from it.
    */
    'reconcile_after_minutes' => 15,

    /*
    | Days Transbank answers `status` for a transaction. Older attempts that
    | are still open are closed as aborted without a call.
    */
    'status_lookup_days' => 7,

    /*
    | Storefront page the customer lands on after Webpay, followed by
    | `/{reference}` (built in phase 4).
    */
    'result_url' => rtrim((string) env('STOREFRONT_URL', 'http://localhost:3000'), '/').'/pedidos',

    /*
    | Transbank Webpay Plus. The defaults are Transbank's public integration
    | credentials; they are not secret, but the key must still never reach a
    | log or an exception (docs/domain/payments.md).
    */
    'webpay_plus' => [
        'environment' => env('TRANSBANK_ENVIRONMENT', 'integration'),
        'hosts' => [
            'integration' => 'https://webpay3gint.transbank.cl',
            'production' => 'https://webpay3g.transbank.cl',
        ],
        'commerce_code' => env('TRANSBANK_COMMERCE_CODE', '597055555532'),
        'api_key' => env('TRANSBANK_API_KEY', '579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C'),

        /*
        | Seconds. The commit window is under a minute: one commit retry on a
        | connection error (commit is idempotent) still fits in it.
        */
        'connect_timeout' => 3,
        'timeout' => 10,
        'commit_retries' => 1,
    ],

];
