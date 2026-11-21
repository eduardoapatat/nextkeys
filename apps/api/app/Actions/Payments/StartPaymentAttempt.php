<?php

namespace App\Actions\Payments;

use App\Actions\Orders\LockPayableOrder;
use App\Actions\Orders\OrderBusy;
use App\Actions\Orders\OrderCannotBePaid;
use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentOrigin;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Payments\PaymentGateway;
use App\Payments\PaymentGatewayException;
use App\Payments\PaymentRedirect;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;

final class StartPaymentAttempt
{
    public function __construct(
        private LockPayableOrder $lockPayableOrder,
        private PaymentGateway $gateway,
    ) {}

    /**
     * Opens a new payment attempt for the order's server-side total and
     * creates its gateway transaction. An expired order is reopened first,
     * and the hold is extended to cover the gateway's payment form. The
     * origin decides where the gateway's return sends the customer.
     *
     * The attempt is stored before the gateway call: its `buy_order` may reach
     * the gateway even when the call fails, so it is never reused.
     *
     * @throws ValidationException when the order ran out of attempts.
     * @throws OrderCannotBePaid
     * @throws OrderBusy
     * @throws PaymentGatewayException
     */
    public function handle(Order $order, PaymentOrigin $origin): PaymentRedirect
    {
        $attempt = $this->lockPayableOrder->handle($order, function (Order $locked) use ($origin): PaymentAttempt {
            $maxAttempts = Config::integer('payments.max_attempts');
            $number = $locked->paymentAttempts()->count() + 1;

            if ($number > $maxAttempts) {
                throw ValidationException::withMessages([
                    'order' => "El pedido alcanzó el máximo de {$maxAttempts} intentos de pago. Crea un pedido nuevo.",
                ]);
            }

            $windowEnd = now()->addMinutes(Config::integer('payments.payment_window_minutes'));

            if ($locked->expires_at->lessThan($windowEnd)) {
                $locked->update(['expires_at' => $windowEnd]);
            }

            return $locked->paymentAttempts()->create([
                'number' => $number,
                'gateway' => PaymentAttempt::GATEWAY_WEBPAY_PLUS,
                'buy_order' => PaymentAttempt::buyOrderFor($locked, $number),
                'amount' => $locked->total,
                'status' => PaymentAttemptStatus::Initialized,
                'origin' => $origin,
            ]);
        });

        try {
            $redirect = $this->gateway->start(
                buyOrder: $attempt->buy_order,
                sessionId: $order->reference,
                amount: $attempt->amount,
                returnUrl: route('payments.webpay.return'),
            );
        } catch (PaymentGatewayException $exception) {
            $attempt->finish(PaymentAttemptStatus::Aborted);

            throw $exception;
        }

        $attempt->update(['token' => $redirect->token]);

        return $redirect;
    }
}
