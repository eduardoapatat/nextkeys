<?php

use App\Actions\Orders\MarkOrderPaid;
use App\Actions\Orders\OrderLineData;
use App\Actions\Orders\PlaceOrder;
use App\Actions\Orders\ShipOrder;
use App\Enums\Carrier;
use App\Jobs\OrderShippedEmailFailed;
use App\Jobs\SendOrderShippedEmail;
use App\Mail\OrderShippedMail;
use App\Models\CustomerAddress;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->customer = User::factory()->create(['email' => 'cliente@example.test']);
    $box = Product::factory()->physical()->published()->create(['stock' => 3]);
    $this->order = app(PlaceOrder::class)->handle(
        $this->customer,
        [new OrderLineData($box->id, 2)],
        CustomerAddress::factory()->for($this->customer)->create([
            'recipient_name' => 'Ana Pérez',
            'phone' => '+56912345678',
            'region' => 'RM',
            'commune' => 'Ñuñoa',
            'street' => 'Pasaje Secreto',
            'street_number' => '742',
        ]),
    );
    app(MarkOrderPaid::class)->handle($this->order);
    $this->boxName = $box->title->name;
});

it('tells the customer the courier, the tracking link and the address', function () {
    Mail::fake();

    app(ShipOrder::class)->handle($this->order, Carrier::Starken, 'AB-12345');

    $sent = Mail::sent(OrderShippedMail::class)->sole();
    expect($sent->hasTo('cliente@example.test'))->toBeTrue()
        ->and($sent->render())
        ->toContain('Starken')
        ->toContain('AB-12345')
        ->toContain('https://www.starken.cl/seguimiento?codigo=AB-12345')
        ->toContain("2 × {$this->boxName}")
        ->toContain('Ana Pérez, Pasaje Secreto 742, Ñuñoa, Metropolitana de Santiago')
        ->toContain('10 días de derecho a retracto')
        ->toContain("/pedidos/{$this->order->reference}");
});

it('keeps the address out of the job payload, the logs and failed_jobs', function () {
    $log = storage_path('logs/order-shipped-email-test.log');
    File::delete($log);
    config([
        'queue.default' => 'database',
        'logging.default' => 'single',
        'logging.channels.single.path' => $log,
    ]);
    DB::table('jobs')->delete();

    app(ShipOrder::class)->handle($this->order, Carrier::Starken, 'AB-12345');

    $payload = DB::table('jobs')->sole()->payload;
    expect(json_decode($payload, true)['displayName'])->toBe(SendOrderShippedEmail::class)
        ->and($payload)->not->toContain('Pasaje Secreto');

    Mail::shouldReceive('to')->andThrow(new RuntimeException('Rendering failed near Pasaje Secreto 742'));
    DB::table('jobs')->update(['payload' => str_replace('"maxTries":5', '"maxTries":1', $payload)]);
    $this->artisan('queue:work', ['--once' => true, '--stop-when-empty' => true]);

    $failed = DB::table('failed_jobs')->sole();
    expect($failed->exception)->toContain(OrderShippedEmailFailed::class)
        ->and($failed->exception)->not->toContain('Pasaje Secreto')
        ->and(File::get($log))->toContain('Shipping email gave up')
        ->and(File::get($log))->not->toContain('Pasaje Secreto');
    File::delete($log);
});

it('refuses to send through the log mailer, which would write the address to the log', function () {
    Mail::fake();
    app(ShipOrder::class)->handle($this->order, Carrier::Starken, 'AB-12345');
    Mail::fake();
    config(['mail.default' => 'log']);

    SendOrderShippedEmail::dispatchSync($this->order->id);

    Mail::assertNothingSent();
});
