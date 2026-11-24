<?php

use Illuminate\Contracts\Queue\ShouldQueue;

/*
 * Pest prints the description on failure, so each one names the fix.
 */

arch('the payment confirmation is sent by paying with MarkOrderPaid (or resending from the panel with ResendOrderPaidEmail), never dispatched elsewhere')
    ->expect('App\Jobs\SendOrderPaidEmail')
    ->toOnlyBeUsedIn([
        'App\Actions\Orders\MarkOrderPaid',
        'App\Actions\Orders\ResendOrderPaidEmail',
    ]);

arch('OrderPaidMail is never queued (its keys would be serialized): queue App\Jobs\SendOrderPaidEmail, which carries only the order id')
    ->expect('App\Mail\OrderPaidMail')
    ->not->toImplement(ShouldQueue::class)
    ->and('App\Mail\OrderPaidMail')
    ->toOnlyBeUsedIn('App\Jobs\SendOrderPaidEmail');

arch('the shipping email is sent by shipping with ShipOrder, never dispatched elsewhere')
    ->expect('App\Jobs\SendOrderShippedEmail')
    ->toOnlyBeUsedIn('App\Actions\Orders\ShipOrder');

arch('OrderShippedMail is never queued (the address would be serialized): queue App\Jobs\SendOrderShippedEmail, which carries only the order id')
    ->expect('App\Mail\OrderShippedMail')
    ->not->toImplement(ShouldQueue::class)
    ->and('App\Mail\OrderShippedMail')
    ->toOnlyBeUsedIn('App\Jobs\SendOrderShippedEmail');
