<?php

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

/**
 * The `/api/v1` routes that answer without a customer session. Any other
 * `/api/v1` route must use `auth:sanctum`.
 */
const PUBLIC_API_ROUTES = [
    'api.v1.products.index',
    'api.v1.products.show',
    'api.v1.catalog.filters',
    'api.v1.shipping.show',
    'api.v1.auth.register',
    'api.v1.auth.login',
    'api.v1.auth.verification.verify',
    'api.v1.auth.password.email',
    'api.v1.auth.password.update',
];

/**
 * @return list<RoutingRoute>
 */
function apiV1Routes(): array
{
    return collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route): bool => str_starts_with($route->uri(), 'api/v1/'))
        ->reject(fn (RoutingRoute $route): bool => $route->getName() === 'sanctum.csrf-cookie')
        ->values()
        ->all();
}

it('authenticates /api/v1 only with the customer Sanctum guard', function () {
    $wrongGuard = collect(apiV1Routes())
        ->filter(fn (RoutingRoute $route): bool => collect($route->gatherMiddleware())
            ->contains(fn (mixed $middleware): bool => is_string($middleware)
                && str_starts_with($middleware, 'auth')
                && $middleware !== 'auth:sanctum'))
        ->map(fn (RoutingRoute $route): string => $route->uri())
        ->values()
        ->all();

    expect($wrongGuard)->toBe([], 'Routes with another auth guard: '.implode(', ', $wrongGuard)
        .'. Storefront routes use `auth:sanctum` (the `customer` guard); `auth` is the admin panel session.');
});

it('requires a customer session on every /api/v1 route not listed as public', function () {
    $unguarded = collect(apiV1Routes())
        ->reject(fn (RoutingRoute $route): bool => in_array($route->getName(), PUBLIC_API_ROUTES, true))
        ->reject(fn (RoutingRoute $route): bool => in_array('auth:sanctum', $route->gatherMiddleware(), true))
        ->map(fn (RoutingRoute $route): string => (string) ($route->getName() ?? $route->uri()))
        ->values()
        ->all();

    expect($unguarded)->toBe([], 'Routes without `auth:sanctum`: '.implode(', ', $unguarded)
        .'. Add the middleware, or list the route in PUBLIC_API_ROUTES if it is meant to be public.');
});

it('serves the CSRF cookie under /api/v1', function () {
    $this->withHeaders(fromStorefront())
        ->getJson('/api/v1/auth/csrf-cookie')
        ->assertNoContent()
        ->assertCookie('XSRF-TOKEN', encrypted: false);
});
