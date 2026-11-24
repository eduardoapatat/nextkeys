<?php

use App\Http\Controllers\Api\V1\Account\AddressController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\RegistrationController;
use App\Http\Controllers\Api\V1\Auth\SessionController;
use App\Http\Controllers\Api\V1\CatalogFilterController;
use App\Http\Controllers\Api\V1\Library\LibraryController;
use App\Http\Controllers\Api\V1\Orders\CustomerOrderController;
use App\Http\Controllers\Api\V1\Orders\CustomerPaymentController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ShippingController;
use Illuminate\Support\Facades\Route;

/*
| The API consumed by the Nuxt storefront: the public, read-only catalog
| (docs/domain/catalog.md, "Public catalog API (phase 2)") and customer
| authentication with Sanctum (docs/domain/users.md). Routes for a signed in
| customer use `auth:sanctum`; tests/Feature/Api/V1/ApiAuthRoutesTest.php
| lists the public ones. The CSRF cookie is Sanctum's
| /api/v1/auth/csrf-cookie (`sanctum.prefix`).
*/
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/{slug}', [ProductController::class, 'show'])->name('products.show');
    Route::get('catalog/filters', CatalogFilterController::class)->name('catalog.filters');
    Route::get('shipping', ShippingController::class)->name('shipping.show');

    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('register', RegistrationController::class)->middleware('throttle:customer-register')->name('register');
        Route::post('login', [SessionController::class, 'store'])->middleware('throttle:customer-login')->name('login');
        Route::post('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
            ->whereNumber('id')
            ->middleware(['signed:relative', 'throttle:6,1'])
            ->name('verification.verify');
        Route::post('forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:customer-password')->name('password.email');
        Route::post('reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:customer-password')->name('password.update');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [SessionController::class, 'show'])->name('me');
            Route::post('logout', [SessionController::class, 'destroy'])->name('logout');
            Route::post('email/verification-notification', [EmailVerificationController::class, 'send'])
                ->middleware('throttle:6,1')
                ->name('verification.send');
        });
    });

    Route::middleware('auth:sanctum')->prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [CustomerOrderController::class, 'index'])->name('index');
        Route::post('/', [CustomerOrderController::class, 'store'])->middleware('throttle:10,1')->name('store');
        Route::get('{reference}', [CustomerOrderController::class, 'show'])->name('show');
        Route::post('{reference}/payments', CustomerPaymentController::class)->middleware('throttle:10,1')->name('payments.store');
        Route::post('{reference}/cancel', [CustomerOrderController::class, 'cancel'])->name('cancel');
    });

    Route::middleware('auth:sanctum')->prefix('library')->name('library.')->group(function () {
        Route::get('/', [LibraryController::class, 'index'])->name('index');
        Route::post('{reference}/{slug}/keys', [LibraryController::class, 'reveal'])->middleware('throttle:30,1')->name('keys');
    });

    Route::middleware('auth:sanctum')->prefix('account/addresses')->name('account.addresses.')->group(function () {
        Route::get('/', [AddressController::class, 'index'])->name('index');
        Route::post('/', [AddressController::class, 'store'])->middleware('throttle:20,1')->name('store');
        Route::put('{publicId}', [AddressController::class, 'update'])->middleware('throttle:20,1')->name('update');
        Route::delete('{publicId}', [AddressController::class, 'destroy'])->name('destroy');
    });
});
