<?php

/*
 * Pest prints the description on failure, so each one names the fix.
 */

arch('only the gateway binding and the Webpay return controller use App\Payments\Transbank: depend on App\Payments\PaymentGateway instead')
    ->expect('App\Payments\Transbank')
    ->toOnlyBeUsedIn([
        'App\Payments\Transbank',
        'App\Providers\AppServiceProvider',
        'App\Http\Controllers\Payments',
    ]);

arch('outgoing HTTP calls live in a gateway implementation (App\Payments\<Provider>) behind an interface, so credentials and failures stay inside it')
    ->expect('Illuminate\Support\Facades\Http')
    ->toOnlyBeUsedIn('App\Payments\Transbank');
