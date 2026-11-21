<?php

namespace App\Actions\Payments;

use App\Enums\PaymentAttemptStatus;
use App\Models\PaymentAttempt;

final class CancelPaymentAttempt
{
    /**
     * Records that the customer cancelled the payment in the gateway. The
     * gateway is not called: the attempt is never committed, so nothing is
     * charged. Both the token and the `buy_order` must match, so a forged
     * request cannot cancel someone else's attempt.
     *
     * @return PaymentAttempt|null null when no attempt matches.
     */
    public function handle(string $token, string $buyOrder): ?PaymentAttempt
    {
        $attempt = PaymentAttempt::query()
            ->where('token', $token)
            ->where('buy_order', $buyOrder)
            ->first();

        $attempt?->finishIfInitialized(PaymentAttemptStatus::Cancelled);

        return $attempt;
    }
}
