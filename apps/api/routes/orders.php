<?php

use App\Http\Controllers\Orders\OrderController;
use Illuminate\Support\Facades\Route;

Route::resource('orders', OrderController::class)
    ->only(['index', 'show'])
    ->parameters(['orders' => 'order:reference']);
Route::post('orders/{order:reference}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
Route::post('orders/{order:reference}/ship', [OrderController::class, 'ship'])->name('orders.ship');
Route::post('orders/{order:reference}/mark-delivered', [OrderController::class, 'markDelivered'])->name('orders.mark-delivered');
Route::post('orders/{order:reference}/mark-paid', [OrderController::class, 'markPaid'])->name('orders.mark-paid');
Route::post('orders/{order:reference}/webpay', [OrderController::class, 'payWithWebpay'])->name('orders.pay-with-webpay');
Route::get('orders/{order:reference}/webpay', [OrderController::class, 'webpayRedirect'])->name('orders.webpay-redirect');
Route::post('orders/{order:reference}/resend-paid-email', [OrderController::class, 'resendPaidEmail'])
    ->middleware('throttle:paid-email-resend')
    ->name('orders.resend-paid-email');
