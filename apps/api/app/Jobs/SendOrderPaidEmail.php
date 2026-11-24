<?php

namespace App\Jobs;

use App\Mail\OrderPaidMail;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends the payment confirmation email, which carries the order's keys
 * (docs/domain/orders.md, "Confirmation email and key delivery").
 *
 * Only the order id is serialized: the keys are decrypted while the mail
 * renders inside handle(), never queued. Dispatched after the outermost
 * transaction commits, so a rolled back payment sends nothing.
 */
final class SendOrderPaidEmail implements ShouldQueueAfterCommit
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 30;

    public function __construct(public readonly int $orderId) {}

    /**
     * Seconds before each retry.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900, 3600];
    }

    public function handle(): void
    {
        if (MailerGuard::writesToLog()) {
            $this->fail(OrderPaidEmailFailed::logMailer());

            return;
        }

        $order = Order::query()->with('user:id,email')->findOrFail($this->orderId);

        try {
            Mail::to($order->user->email)->send(new OrderPaidMail($order));
        } catch (Throwable $exception) {
            throw OrderPaidEmailFailed::from($order->reference, $exception);
        }

        $order->forceFill(['paid_email_sent_at' => now()])->save();
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Payment confirmation email gave up', [
            'order' => Order::query()->whereKey($this->orderId)->value('reference'),
            'exception' => $exception === null ? null : $exception::class,
        ]);
    }
}
