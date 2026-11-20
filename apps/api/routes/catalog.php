<?php

use App\Http\Controllers\Catalog\ProductController;
use App\Http\Controllers\Catalog\TitleController;
use Illuminate\Support\Facades\Route;

/*
 * Catalog management of the admin panel. Required from the admin group of
 * routes/web.php, which applies `auth` and `can:access-admin-panel`.
 */
Route::prefix('catalog')->group(function () {
    Route::resource('titles', TitleController::class)->except('show');
    Route::resource('products', ProductController::class)->except('show');
});
