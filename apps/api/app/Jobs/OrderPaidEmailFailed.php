<?php

namespace App\Jobs;

use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

/**
 * What a failed payment confirmation email reports. It never holds the
 * original exception: a rendering error or a stack trace with arguments
 * could carry the decrypted keys into the logs and `failed_jobs`.
 */
final class OrderPaidEmailFailed extends RuntimeException
{
    public static function from(string $reference, Throwable $exception): self
    {
        $detail = $exception instanceof TransportExceptionInterface
            ? ': '.mb_substr($exception->getMessage(), 0, 200)
            : '';

        return new self('Payment confirmation email for '.$reference.' failed with '.$exception::class.$detail);
    }

    public static function logMailer(): self
    {
        return new self('MAIL_MAILER=log escribiría las claves en el log; usa smtp (Mailpit en local, ver docs/domain/orders.md).');
    }
}
