<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Jobs\SendOrderPaidEmail;
use App\Models\Order;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ResendOrderPaidEmail
{
    /**
     * Queues the payment confirmation (with the keys) again, always to the
     * customer's current email, up to `orders.max_paid_email_resends` times.
     *
     * @throws ValidationException
     */
    public function handle(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->sole();

            if (! in_array($locked->status, [OrderStatus::Paid, OrderStatus::Completed], true)) {
                throw ValidationException::withMessages([
                    'order' => 'Solo se puede reenviar el email de un pedido pagado.',
                ]);
            }

            $maxResends = Config::integer('orders.max_paid_email_resends');

            if ($locked->paid_email_resends >= $maxResends) {
                throw ValidationException::withMessages([
                    'order' => "El email ya se reenvió {$maxResends} veces.",
                ]);
            }

            $locked->increment('paid_email_resends');

            SendOrderPaidEmail::dispatch($locked->id);
        });

        $order->refresh();
    }
}
