<?php

namespace App\Actions\Payments;

use App\Actions\Orders\LockPayableOrder;
use App\Actions\Orders\MarkOrderPaid;
use App\Actions\Orders\OrderBusy;
use App\Actions\Orders\OrderCannotBePaid;
use App\Enums\PaymentAttemptStatus;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Payments\GatewayPaymentState;
use App\Payments\PaymentGateway;
use App\Payments\PaymentGatewayException;
use Illuminate\Support\Facades\Log;

final class ConfirmPaymentAttempt
{
    public function __construct(
        private LockPayableOrder $lockPayableOrder,
        private MarkOrderPaid $markOrderPaid,
        private PaymentGateway $gateway,
    ) {}

    /**
     * Commits the payment the customer just made, in the same request as
     * their return (the gateway's commit window is under a minute), and only
     * while holding a payable order: an order that can no longer take the
     * payment is never committed, so the customer is never charged for it.
     *
     * Idempotent: an attempt that is no longer `initialized` is returned as
     * is. When the outcome is unknown (busy order, gateway error) the attempt
     * stays `initialized` and `payments:reconcile` settles it.
     */
    public function handle(PaymentAttempt $attempt): PaymentAttemptStatus
    {
        $attempt->refresh();
        $token = $attempt->token;

        if ($attempt->status !== PaymentAttemptStatus::Initialized || $token === null) {
            return $attempt->status;
        }

        try {
            $this->lockPayableOrder->handle($attempt->order, function (Order $locked) use ($attempt, $token): void {
                $current = PaymentAttempt::query()->whereKey($attempt->id)->lockForUpdate()->sole();

                if ($current->status !== PaymentAttemptStatus::Initialized) {
                    return;
                }

                $payment = $this->gateway->confirm($token);

                if ($payment->isApproved() && $payment->matches($current->buy_order, $current->amount)) {
                    $this->markOrderPaid->handle($locked);
                    $current->finish(PaymentAttemptStatus::Authorized, $payment);
                } elseif ($payment->isApproved()) {
                    $current->finish(
                        PaymentAttemptStatus::Refunded,
                        $payment,
                        $this->gateway->refund($token, $payment->amount)->type,
                    );
                } elseif ($payment->state === GatewayPaymentState::Failed) {
                    $current->finish(PaymentAttemptStatus::Rejected, $payment);
                } else {
                    Log::warning('Payment attempt left for reconciliation: unexpected commit result.', [
                        'buy_order' => $current->buy_order,
                        'state' => $payment->state->name,
                        'response_code' => $payment->responseCode,
                    ]);
                }
            });
        } catch (OrderCannotBePaid) {
            $attempt->finishIfInitialized(PaymentAttemptStatus::Aborted);
        } catch (OrderBusy|PaymentGatewayException $exception) {
            Log::warning('Payment attempt left for reconciliation.', [
                'buy_order' => $attempt->buy_order,
                'reason' => $exception->getMessage(),
            ]);
        }

        return $attempt->refresh()->status;
    }
}
