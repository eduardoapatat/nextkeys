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

arch('outgoing HTTP calls live in a provider implementation behind an interface (App\Payments\<Provider> behind PaymentGateway, App\Catalog\CoverArt\<Provider> behind CoverArtSource), so credentials and failures stay inside it')
    ->expect('Illuminate\Support\Facades\Http')
    ->toOnlyBeUsedIn(['App\Payments\Transbank', 'App\Catalog\CoverArt\Igdb']);

arch('only the cover art binding uses App\Catalog\CoverArt\Igdb: depend on App\Catalog\CoverArt\CoverArtSource instead')
    ->expect('App\Catalog\CoverArt\Igdb')
    ->toOnlyBeUsedIn(['App\Catalog\CoverArt\Igdb', 'App\Providers\AppServiceProvider']);
