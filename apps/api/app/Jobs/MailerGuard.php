<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Config;

/**
 * Whether the default mailer writes whole messages to the application log
 * (the `log` transport, also inside a failover or round robin mailer). Order
 * emails carry keys or a home address, so their jobs refuse it.
 */
final class MailerGuard
{
    public static function writesToLog(): bool
    {
        $mailer = Config::string('mail.default');
        $transport = Config::get("mail.mailers.{$mailer}.transport");
        $mailers = in_array($transport, ['failover', 'roundrobin'], true)
            ? (array) Config::get("mail.mailers.{$mailer}.mailers", [])
            : [$mailer];

        foreach ($mailers as $name) {
            if (is_string($name) && Config::get("mail.mailers.{$name}.transport") === 'log') {
                return true;
            }
        }

        return false;
    }
}
