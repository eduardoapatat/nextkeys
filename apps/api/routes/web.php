<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome'))->name('home');

Route::get('.well-known/passkey-endpoints', fn () => response()->json([
    'enroll' => route('security.edit'),
    'manage' => route('security.edit'),
]))->name('well-known.passkeys');

require __DIR__.'/payments.php';

/*
 * The admin panel. Every authenticated route belongs in this group, so it is
 * restricted to admins (enforced by tests/Feature/AdminPanelRoutesTest.php).
 */
Route::middleware(['auth', 'can:access-admin-panel'])->group(function () {
    Route::get('dashboard', fn () => Inertia::render('Dashboard'))
        ->middleware('verified')
        ->name('dashboard');

    require __DIR__.'/settings.php';
    require __DIR__.'/catalog.php';
    require __DIR__.'/orders.php';
});
