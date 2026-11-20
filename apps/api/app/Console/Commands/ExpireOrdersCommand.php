<?php

namespace App\Console\Commands;

use App\Actions\Orders\ExpireOrders;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:expire')]
#[Description('Expire pending orders past their reservation and release their stock')]
class ExpireOrdersCommand extends Command
{
    public function handle(ExpireOrders $expireOrders): int
    {
        $expired = $expireOrders->handle();

        $this->info("Expired {$expired} order(s).");

        return self::SUCCESS;
    }
}
