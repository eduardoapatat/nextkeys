<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;

/*
| Roles are exclusive: an admin does not exist for the storefront, and a
| storefront session never opens the panel. See docs/domain/users.md.
*/

beforeEach(function () {
    $this->withHeaders(fromStorefront());
});

it('rejects an admin signing in to the storefront like unknown credentials', function () {
    User::factory()->admin()->create(['email' => 'admin@example.com']);

    $this->postJson(route('api.v1.auth.login'), ['email' => 'admin@example.com', 'password' => 'password'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'Estas credenciales no son válidas.']);

    $this->assertGuest('customer');
});

it('does not see the admin panel session', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin, 'web')
        ->getJson(route('api.v1.auth.me'))
        ->assertUnauthorized();
});

it('sends no storefront reset link to an admin', function () {
    Notification::fake();
    User::factory()->admin()->create(['email' => 'admin@example.com']);

    $this->postJson(route('api.v1.auth.password.email'), ['email' => 'admin@example.com'])->assertOk();

    Notification::assertNothingSent();
});

it('keeps a storefront customer out of the panel', function () {
    $customer = User::factory()->create(['email' => 'ana@example.com']);

    $this->postJson(route('api.v1.auth.login'), ['email' => 'ana@example.com', 'password' => 'password'])->assertOk();

    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->actingAs($customer, 'web')->get(route('dashboard'))->assertForbidden();
});
