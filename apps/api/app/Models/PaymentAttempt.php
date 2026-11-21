<?php

namespace App\Models;

use App\Enums\PaymentAttemptStatus;
use App\Payments\GatewayPayment;
use App\Payments\RefundType;
use Database\Factories\PaymentAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * One attempt to pay an order through a gateway (docs/domain/payments.md,
 * "Design"). Only an `initialized` attempt changes, once.
 *
 * @property int $id
 * @property int $order_id
 * @property int $number 1 to `payments.max_attempts`.
 * @property string $gateway
 * @property string $buy_order `{order reference}-{number}`, unique per transaction.
 * @property string|null $token Null until the gateway created the transaction.
 * @property int $amount
 * @property PaymentAttemptStatus $status
 * @property int|null $response_code
 * @property string|null $authorization_code
 * @property string|null $payment_type_code
 * @property int|null $installments_number
 * @property int|null $installments_amount
 * @property string|null $card_last_digits
 * @property Carbon|null $transaction_date
 * @property string|null $accounting_date MMDD.
 * @property RefundType|null $refund_type
 * @property int|null $refunded_amount
 * @property Carbon|null $refunded_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Order $order
 */
#[Fillable(['order_id', 'number', 'gateway', 'buy_order', 'token', 'amount', 'status'])]
class PaymentAttempt extends Model
{
    /** @use HasFactory<PaymentAttemptFactory> */
    use HasFactory;

    public const string GATEWAY_WEBPAY_PLUS = 'webpay_plus';

    public static function buyOrderFor(Order $order, int $number): string
    {
        return "{$order->reference}-{$number}";
    }

    /**
     * Closes the attempt with its final status, the receipt the gateway
     * reported and, when refunded, the refund.
     */
    public function finish(PaymentAttemptStatus $status, ?GatewayPayment $payment = null, ?RefundType $refund = null): void
    {
        $receipt = $payment?->receipt;

        $this->forceFill([
            'status' => $status,
            'finished_at' => now(),
            'response_code' => $payment?->responseCode,
            'authorization_code' => $receipt?->authorizationCode,
            'payment_type_code' => $receipt?->paymentTypeCode,
            'installments_number' => $receipt?->installmentsNumber,
            'installments_amount' => $receipt?->installmentsAmount,
            'card_last_digits' => $receipt?->cardLastDigits,
            'transaction_date' => $receipt?->transactionDate,
            'accounting_date' => $receipt?->accountingDate,
            'refund_type' => $refund,
            'refunded_amount' => $refund === null ? null : ($payment->amount ?? $this->amount),
            'refunded_at' => $refund === null ? null : now(),
        ])->save();
    }

    /**
     * Like finish(), unless the attempt was already closed by a concurrent
     * request: only an `initialized` attempt changes.
     */
    public function finishIfInitialized(PaymentAttemptStatus $status, ?GatewayPayment $payment = null, ?RefundType $refund = null): void
    {
        DB::transaction(function () use ($status, $payment, $refund): void {
            $locked = self::query()->whereKey($this->id)->lockForUpdate()->sole();

            if ($locked->status === PaymentAttemptStatus::Initialized) {
                $locked->finish($status, $payment, $refund);
            }
        });

        $this->refresh();
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'amount' => 'integer',
            'status' => PaymentAttemptStatus::class,
            'response_code' => 'integer',
            'installments_number' => 'integer',
            'installments_amount' => 'integer',
            'transaction_date' => 'datetime',
            'refund_type' => RefundType::class,
            'refunded_amount' => 'integer',
            'refunded_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
