<?php

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

it('restricts every authenticated application route to admins', function () {
    $unguarded = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route): bool => isApplicationRoute($route))
        ->filter(fn (RoutingRoute $route): bool => in_array('auth', $route->gatherMiddleware(), true))
        ->reject(fn (RoutingRoute $route): bool => in_array('can:access-admin-panel', $route->gatherMiddleware(), true))
        ->map(fn (RoutingRoute $route): string => $route->uri())
        ->values()
        ->all();

    expect($unguarded)->toBe([], 'Authenticated routes outside the admin gate: '.implode(', ', $unguarded)
        .'. Declare them inside the `auth` + `can:access-admin-panel` group in routes/web.php.');
});

/**
 * Whether the route is declared by this application (a closure or an `App\`
 * controller), as opposed to a vendor package such as Fortify.
 */
function isApplicationRoute(RoutingRoute $route): bool
{
    $uses = $route->getAction('uses');

    return $uses instanceof Closure || (is_string($uses) && str_starts_with($uses, 'App\\'));
}
