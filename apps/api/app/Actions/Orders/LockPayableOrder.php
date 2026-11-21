<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * The single rule for "can this order still be charged?". It runs the
 * callback in a transaction that holds the order's row lock, so nothing can
 * expire or cancel the order until the callback (e.g. a gateway commit and
 * MarkOrderPaid) finishes.
 */
final class LockPayableOrder
{
    /** SQLSTATE `lock_not_available`, raised when `lock_timeout` runs out. */
    private const string LOCK_NOT_AVAILABLE = '55P03';

    public function __construct(private ReopenExpiredOrder $reopenExpiredOrder) {}

    /**
     * Locks the order, waiting at most `orders.payment_lock_timeout_seconds`
     * (the timeout lasts until the outer transaction ends). A pending order
     * is handed to the callback as is; an expired one is reopened first.
     *
     * @template TResult
     *
     * @param  Closure(Order): TResult  $callback  receives the locked order.
     * @return TResult
     *
     * @throws OrderCannotBePaid when the order is paid, cancelled, or expired without stock.
     * @throws OrderBusy when the lock is not granted in time.
     * @throws Throwable whatever the callback throws, after rolling back.
     */
    public function handle(Order $order, Closure $callback): mixed
    {
        try {
            return DB::transaction(function () use ($order, $callback): mixed {
                DB::statement(sprintf(
                    "SET LOCAL lock_timeout = '%ds'",
                    Config::integer('orders.payment_lock_timeout_seconds'),
                ));

                $locked = Order::query()->whereKey($order->id)->lockForUpdate()->sole();

                match ($locked->status) {
                    OrderStatus::Pending => null,
                    OrderStatus::Expired => $this->reopenExpiredOrder->handle($locked),
                    OrderStatus::Paid, OrderStatus::Completed => throw OrderCannotBePaid::alreadyPaid($locked),
                    OrderStatus::Cancelled => throw OrderCannotBePaid::cancelled($locked),
                };

                return $callback($locked);
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === self::LOCK_NOT_AVAILABLE) {
                throw OrderBusy::for($order);
            }

            throw $exception;
        }
    }
}
