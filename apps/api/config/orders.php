<?php

return [

    /*
    | Minutes a pending order holds its keys and physical stock before the
    | `orders:expire` command releases them (docs/domain/orders.md).
    */
    'reservation_minutes' => 15,

    /*
    | Units of one product per order line. The `order_items_quantity_range`
    | CHECK enforces the same limit in the database.
    */
    'max_quantity_per_line' => 5,

    /*
    | Lets admins mark an order as paid from the panel, to try orders without
    | a payment gateway. Local only: in production it would deliver keys
    | without charging, so it is off unless the environment turns it on.
    */
    'manual_payments' => (bool) env('ORDERS_MANUAL_PAYMENTS', false),

    /*
    | Seconds a payment waits for the order's row lock before giving up
    | (`LockPayableOrder`). Short, so a busy lock cannot push a Webpay commit
    | past its window of under a minute (docs/domain/payments.md).
    */
    'payment_lock_timeout_seconds' => 3,

];
