<?php

use App\Http\Controllers\Payments\WebpayReturnController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

/*
 * Webpay sends the customer back by GET or POST (an abort comes by POST in
 * integration and GET in production), from its own domain: no CSRF token.
 * The token identifies the attempt, so it needs no session either.
 */
Route::match(['get', 'post'], 'payments/webpay/return', WebpayReturnController::class)
    ->withoutMiddleware(PreventRequestForgery::class)
    ->middleware('throttle:30,1')
    ->name('payments.webpay.return');
