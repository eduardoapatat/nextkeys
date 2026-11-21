<?php

namespace App\Actions\Payments;

use App\Actions\Orders\LockPayableOrder;
use App\Actions\Orders\MarkOrderPaid;
use App\Actions\Orders\OrderBusy;
use App\Actions\Orders\OrderCannotBePaid;
use App\Enums\PaymentAttemptStatus;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Payments\GatewayPayment;
use App\Payments\GatewayPaymentState;
use App\Payments\PaymentGateway;
use App\Payments\PaymentGatewayException;
use App\Payments\RefundType;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

final class ReconcilePaymentAttempts
{
    public function __construct(
        private LockPayableOrder $lockPayableOrder,
        private MarkOrderPaid $markOrderPaid,
        private PaymentGateway $gateway,
    ) {}

    /**
     * Settles the attempts their return left open (docs/domain/payments.md,
     * "Reconciliation"). Only attempts older than the gateway's form and
     * commit windows are read, so a customer still paying is never raced.
     * A charge the order cannot take is refunded at once.
     *
     * @return int How many attempts were settled.
     */
    public function handle(): int
    {
        $settled = 0;

        PaymentAttempt::query()
            ->where('status', PaymentAttemptStatus::Initialized)
            ->where('created_at', '<', now()->subMinutes(Config::integer('payments.reconcile_after_minutes')))
            ->with('order')
            ->lazyById(100)
            ->each(function (PaymentAttempt $attempt) use (&$settled): void {
                try {
                    $this->reconcile($attempt);
                } catch (OrderBusy|PaymentGatewayException $exception) {
                    Log::warning('Payment attempt not reconciled, retrying on the next run.', [
                        'buy_order' => $attempt->buy_order,
                        'reason' => $exception->getMessage(),
                    ]);
                }

                if ($attempt->status !== PaymentAttemptStatus::Initialized) {
                    $settled++;
                }
            });

        return $settled;
    }

    /**
     * @throws OrderBusy
     * @throws PaymentGatewayException
     */
    private function reconcile(PaymentAttempt $attempt): void
    {
        $tooOld = $attempt->created_at?->lessThan(now()->subDays(Config::integer('payments.status_lookup_days'))) ?? false;

        if ($attempt->token === null || $tooOld) {
            $attempt->finishIfInitialized(PaymentAttemptStatus::Aborted);

            return;
        }

        $payment = $this->gateway->status($attempt->token);

        match ($payment->state) {
            GatewayPaymentState::Initialized => $attempt->finishIfInitialized(PaymentAttemptStatus::Aborted),
            GatewayPaymentState::Failed => $attempt->finishIfInitialized(PaymentAttemptStatus::Rejected, $payment),
            GatewayPaymentState::Reversed => $attempt->finishIfInitialized(PaymentAttemptStatus::Refunded, $payment, RefundType::Reversed),
            GatewayPaymentState::Nullified, GatewayPaymentState::PartiallyNullified => $attempt->finishIfInitialized(PaymentAttemptStatus::Refunded, $payment, RefundType::Nullified),
            GatewayPaymentState::Authorized => $this->settleCharge($attempt, $attempt->token, $payment),
        };
    }

    /**
     * A charge nobody recorded: it goes to the order when the order can still
     * take it, and is refunded otherwise.
     *
     * @throws OrderBusy
     * @throws PaymentGatewayException
     */
    private function settleCharge(PaymentAttempt $attempt, string $token, GatewayPayment $payment): void
    {
        if ($payment->isApproved() && $payment->matches($attempt->buy_order, $attempt->amount)) {
            try {
                $this->lockPayableOrder->handle($attempt->order, function (Order $locked) use ($attempt, $payment): void {
                    $current = PaymentAttempt::query()->whereKey($attempt->id)->lockForUpdate()->sole();

                    if ($current->status === PaymentAttemptStatus::Initialized) {
                        $this->markOrderPaid->handle($locked);
                        $current->finish(PaymentAttemptStatus::Authorized, $payment);
                    }
                });
                $attempt->refresh();

                return;
            } catch (OrderCannotBePaid) {
                // The order cannot take it: refund below.
            }
        }

        $refund = $this->gateway->refund($token, $payment->amount);
        $attempt->finishIfInitialized(PaymentAttemptStatus::Refunded, $payment, $refund->type);

        Log::info('Payment refunded automatically: the order could not take it.', [
            'buy_order' => $attempt->buy_order,
            'amount' => $payment->amount,
        ]);
    }
}
