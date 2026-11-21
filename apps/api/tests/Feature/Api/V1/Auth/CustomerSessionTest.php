<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

beforeEach(function () {
    $this->withHeaders(fromStorefront());
});

it('signs a customer in and returns them', function () {
    $customer = User::factory()->create(['name' => 'Ana', 'email' => 'ana@example.com']);

    $this->postJson(route('api.v1.auth.login'), ['email' => 'ana@example.com', 'password' => 'password'])
        ->assertOk()
        ->assertExactJson(['data' => ['name' => 'Ana', 'email' => 'ana@example.com', 'email_verified' => true]]);

    $this->assertAuthenticatedAs($customer, 'customer');
});

it('accepts the email in any case', function () {
    $customer = User::factory()->create(['email' => 'ana@example.com']);

    $this->postJson(route('api.v1.auth.login'), ['email' => ' ANA@example.com', 'password' => 'password'])->assertOk();

    $this->assertAuthenticatedAs($customer, 'customer');
});

it('regenerates the session on sign in', function () {
    User::factory()->create(['email' => 'ana@example.com']);
    Session::start();
    $before = Session::getId();

    $this->postJson(route('api.v1.auth.login'), ['email' => 'ana@example.com', 'password' => 'password'])->assertOk();

    expect(Session::getId())->not->toBe($before);
});

it('sets the remember cookie only when asked', function (bool $remember) {
    User::factory()->create(['email' => 'ana@example.com']);
    $cookie = Auth::guard('customer')->getRecallerName();

    $response = $this->postJson(route('api.v1.auth.login'), [
        'email' => 'ana@example.com',
        'password' => 'password',
        'remember' => $remember,
    ])->assertOk();

    $remember ? $response->assertCookie($cookie) : $response->assertCookieMissing($cookie);
})->with([true, false]);

it('rejects wrong credentials with one generic message', function (string $email, string $password) {
    User::factory()->create(['email' => 'ana@example.com']);

    $this->postJson(route('api.v1.auth.login'), ['email' => $email, 'password' => $password])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'Estas credenciales no son válidas.']);

    $this->assertGuest('customer');
})->with([
    'wrong password' => ['ana@example.com', 'wrong-password'],
    'unknown email' => ['nobody@example.com', 'password'],
]);

it('requires an email and a password', function () {
    $this->postJson(route('api.v1.auth.login'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

it('limits sign in attempts to 5 per minute per email and IP', function () {
    User::factory()->create(['email' => 'ana@example.com']);

    foreach (range(1, 5) as $attempt) {
        $this->postJson(route('api.v1.auth.login'), ['email' => 'ana@example.com', 'password' => 'wrong'])->assertUnprocessable();
    }

    $this->postJson(route('api.v1.auth.login'), ['email' => 'ana@example.com', 'password' => 'password'])->assertTooManyRequests();
    $this->postJson(route('api.v1.auth.login'), ['email' => 'other@example.com', 'password' => 'wrong'])->assertUnprocessable();
});

it('returns the signed in customer', function () {
    $customer = User::factory()->unverified()->create(['name' => 'Ana', 'email' => 'ana@example.com']);

    $this->actingAs($customer, 'customer')
        ->getJson(route('api.v1.auth.me'))
        ->assertOk()
        ->assertExactJson(['data' => ['name' => 'Ana', 'email' => 'ana@example.com', 'email_verified' => false]]);
});

it('answers 401 without a session', function () {
    $this->getJson(route('api.v1.auth.me'))->assertUnauthorized();
    $this->postJson(route('api.v1.auth.logout'))->assertUnauthorized();
});

it('signs the customer out', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer, 'customer')
        ->postJson(route('api.v1.auth.logout'))
        ->assertNoContent();

    $this->assertGuest('customer');
});
