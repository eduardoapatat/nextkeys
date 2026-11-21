<?php

namespace App\Console\Commands;

use App\Actions\Payments\ReconcilePaymentAttempts;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('payments:reconcile')]
#[Description('Settle payment attempts left open: record unrecorded charges or refund them')]
class ReconcilePaymentAttemptsCommand extends Command
{
    public function handle(ReconcilePaymentAttempts $reconcilePaymentAttempts): int
    {
        $settled = $reconcilePaymentAttempts->handle();

        $this->info("Settled {$settled} payment attempt(s).");

        return self::SUCCESS;
    }
}
