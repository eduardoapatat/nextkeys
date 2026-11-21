<?php

namespace App\Payments\Transbank;

use App\Payments\GatewayPayment;
use App\Payments\GatewayPaymentState;
use App\Payments\GatewayRefund;
use App\Payments\PaymentGatewayException;
use App\Payments\PaymentReceipt;
use App\Payments\RefundType;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * A successful Webpay response body, read field by field: a missing or
 * mistyped required field is an error, never a null (the official SDK fills
 * its objects with nulls instead).
 */
final readonly class WebpayResponse
{
    /**
     * @param  array<array-key, mixed>  $body
     */
    public function __construct(private string $operation, private array $body) {}

    /**
     * @throws PaymentGatewayException
     */
    public function string(string $field): string
    {
        $value = $this->body[$field] ?? null;

        if (! is_string($value) || $value === '') {
            throw $this->malformed($field);
        }

        return $value;
    }

    /**
     * The body of a commit or status call.
     *
     * @throws PaymentGatewayException
     */
    public function toPayment(): GatewayPayment
    {
        $state = match ($this->string('status')) {
            'INITIALIZED' => GatewayPaymentState::Initialized,
            'AUTHORIZED' => GatewayPaymentState::Authorized,
            'FAILED' => GatewayPaymentState::Failed,
            'REVERSED' => GatewayPaymentState::Reversed,
            'NULLIFIED' => GatewayPaymentState::Nullified,
            'PARTIALLY_NULLIFIED' => GatewayPaymentState::PartiallyNullified,
            default => throw $this->malformed('status'),
        };

        $receipt = $state === GatewayPaymentState::Initialized ? null : $this->receipt($state);

        return new GatewayPayment(
            state: $state,
            buyOrder: $this->string('buy_order'),
            amount: $this->int('amount'),
            responseCode: $this->optionalInt('response_code'),
            receipt: $receipt,
        );
    }

    /**
     * The body of a refund call.
     *
     * @throws PaymentGatewayException
     */
    public function toRefund(): GatewayRefund
    {
        return match ($this->string('type')) {
            'REVERSED' => new GatewayRefund(RefundType::Reversed),
            'NULLIFIED' => $this->int('response_code') === 0
                ? new GatewayRefund(RefundType::Nullified)
                : throw PaymentGatewayException::malformed($this->operation, 'the annulment was not approved'),
            default => throw $this->malformed('type'),
        };
    }

    /**
     * Required when the payment was authorized; a declined one may lack it.
     *
     * @throws PaymentGatewayException
     */
    private function receipt(GatewayPaymentState $state): ?PaymentReceipt
    {
        try {
            $cardDetail = $this->body['card_detail'] ?? null;
            $cardNumber = is_array($cardDetail) ? ($cardDetail['card_number'] ?? null) : null;
            $cardLastDigits = is_string($cardNumber) ? substr($cardNumber, -4) : '';

            if (preg_match('/^\d{4}$/', $cardLastDigits) !== 1) {
                throw $this->malformed('card_detail.card_number');
            }

            $accountingDate = $this->string('accounting_date');

            if (preg_match('/^\d{4}$/', $accountingDate) !== 1) {
                throw $this->malformed('accounting_date');
            }

            $paymentTypeCode = $this->string('payment_type_code');

            if (preg_match('/^[A-Z0-9]{2}$/', $paymentTypeCode) !== 1) {
                throw $this->malformed('payment_type_code');
            }

            return new PaymentReceipt(
                authorizationCode: $this->string('authorization_code'),
                paymentTypeCode: $paymentTypeCode,
                installmentsNumber: $this->int('installments_number'),
                installmentsAmount: $this->optionalInt('installments_amount'),
                cardLastDigits: $cardLastDigits,
                transactionDate: $this->date('transaction_date'),
                accountingDate: $accountingDate,
            );
        } catch (PaymentGatewayException $exception) {
            if ($state === GatewayPaymentState::Authorized) {
                throw $exception;
            }

            return null;
        }
    }

    /**
     * @throws PaymentGatewayException
     */
    private function int(string $field): int
    {
        return $this->optionalInt($field) ?? throw $this->malformed($field);
    }

    /**
     * @throws PaymentGatewayException
     */
    private function optionalInt(string $field): ?int
    {
        $value = $this->body[$field] ?? null;

        if ($value !== null && ! is_int($value)) {
            throw $this->malformed($field);
        }

        return $value;
    }

    /**
     * @throws PaymentGatewayException
     */
    private function date(string $field): CarbonImmutable
    {
        try {
            return CarbonImmutable::parse($this->string($field))->utc();
        } catch (PaymentGatewayException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw $this->malformed($field);
        }
    }

    private function malformed(string $field): PaymentGatewayException
    {
        return PaymentGatewayException::malformed($this->operation, "missing or invalid `{$field}`");
    }
}
