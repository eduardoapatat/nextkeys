<?php

use App\Http\Controllers\Orders\OrderController;
use Illuminate\Support\Facades\Route;

Route::resource('orders', OrderController::class)
    ->only(['index', 'show'])
    ->parameters(['orders' => 'order:reference']);
Route::post('orders/{order:reference}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
Route::post('orders/{order:reference}/mark-paid', [OrderController::class, 'markPaid'])->name('orders.mark-paid');
