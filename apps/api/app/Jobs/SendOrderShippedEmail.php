<?php

namespace App\Jobs;

use App\Mail\OrderShippedMail;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Tells the customer their parcel shipped, with the courier and the tracking
 * link (docs/domain/shipping.md). Only the order id is serialized, never the
 * address; dispatched after the transaction of `ShipOrder` commits.
 */
final class SendOrderShippedEmail implements ShouldQueueAfterCommit
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
            $this->fail(OrderShippedEmailFailed::logMailer());

            return;
        }

        $order = Order::query()->with('user:id,email')->findOrFail($this->orderId);

        try {
            Mail::to($order->user->email)->send(new OrderShippedMail($order));
        } catch (Throwable $exception) {
            throw OrderShippedEmailFailed::from($order->reference, $exception);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Shipping email gave up', [
            'order' => Order::query()->whereKey($this->orderId)->value('reference'),
            'exception' => $exception === null ? null : $exception::class,
        ]);
    }
}
