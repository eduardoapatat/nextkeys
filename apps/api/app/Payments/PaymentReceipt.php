<?php

namespace App\Payments;

use Carbon\CarbonImmutable;

/**
 * What the customer's receipt shows about a charge (docs/domain/payments.md,
 * "Transbank requirements for the store").
 */
final readonly class PaymentReceipt
{
    /**
     * @param  string  $paymentTypeCode  `VD` debit, `VN` credit, `VC`/`SI`/`S2`/`NC` installments, `VP` prepaid.
     * @param  string  $accountingDate  MMDD.
     */
    public function __construct(
        public string $authorizationCode,
        public string $paymentTypeCode,
        public int $installmentsNumber,
        public ?int $installmentsAmount,
        public string $cardLastDigits,
        public CarbonImmutable $transactionDate,
        public string $accountingDate,
    ) {}
}
