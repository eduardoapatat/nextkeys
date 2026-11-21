<?php

namespace Database\Factories;

use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentOrigin;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Payments\RefundType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentAttempt>
 */
class PaymentAttemptFactory extends Factory
{
    /**
     * Define the model's default state: the first attempt of a pending order,
     * sent to Webpay and not back yet. The buy order and the amount are taken
     * from the order in `configure()`, unless given.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'number' => 1,
            'gateway' => PaymentAttempt::GATEWAY_WEBPAY_PLUS,
            'token' => fn (): string => hash('sha256', fake()->unique()->uuid()),
            'status' => PaymentAttemptStatus::Initialized,
            'origin' => PaymentOrigin::Storefront,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (PaymentAttempt $attempt): void {
            $order = Order::query()->findOrFail($attempt->order_id);

            $attempt->fill([
                'buy_order' => $attempt->getAttribute('buy_order') ?? PaymentAttempt::buyOrderFor($order, $attempt->number),
                'amount' => $attempt->getAttribute('amount') ?? $order->total,
            ]);
        });
    }

    /**
     * Charged and recorded on its order.
     */
    public function authorized(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentAttemptStatus::Authorized,
            ...$this->receipt(),
        ]);
    }

    /**
     * Charged and refunded by reversal.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentAttemptStatus::Refunded,
            ...$this->receipt(),
            'refund_type' => RefundType::Reversed,
            'refunded_at' => now(),
        ])->afterMaking(function (PaymentAttempt $attempt): void {
            $attempt->refunded_amount ??= $attempt->amount;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function receipt(): array
    {
        return [
            'finished_at' => now(),
            'response_code' => 0,
            'authorization_code' => '1213',
            'payment_type_code' => 'VN',
            'installments_number' => 0,
            'card_last_digits' => '6623',
            'transaction_date' => now(),
            'accounting_date' => now()->format('md'),
        ];
    }
}
