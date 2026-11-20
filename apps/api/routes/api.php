<?php

use App\Http\Controllers\Api\V1\CatalogFilterController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

/*
| Public, read-only catalog API consumed by the Nuxt storefront.
| See docs/domain/catalog.md, "Public catalog API (phase 2)".
*/
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/{slug}', [ProductController::class, 'show'])->name('products.show');
    Route::get('catalog/filters', CatalogFilterController::class)->name('catalog.filters');
});
