<?php

namespace App\Jobs;

use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

/**
 * What a failed shipping email reports. It never holds the original
 * exception, which could carry the customer's address and phone into the
 * logs and `failed_jobs`.
 */
final class OrderShippedEmailFailed extends RuntimeException
{
    public static function from(string $reference, Throwable $exception): self
    {
        $detail = $exception instanceof TransportExceptionInterface
            ? ': '.mb_substr($exception->getMessage(), 0, 200)
            : '';

        return new self('Shipping email for '.$reference.' failed with '.$exception::class.$detail);
    }

    public static function logMailer(): self
    {
        return new self('MAIL_MAILER=log escribiría la dirección del cliente en el log; usa smtp (Mailpit en local, ver docs/domain/orders.md).');
    }
}
