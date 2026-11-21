<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\CustomerVerifyEmail;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    $this->withHeaders(fromStorefront());
});

function registration(array $overrides = []): array
{
    return [
        'name' => 'Ana Pérez',
        'email' => 'ana@example.com',
        'password' => 'una-clave-larga-123',
        'password_confirmation' => 'una-clave-larga-123',
        ...$overrides,
    ];
}

it('registers a customer and signs them in', function () {
    $this->postJson(route('api.v1.auth.register'), registration())
        ->assertCreated()
        ->assertExactJson(['data' => ['name' => 'Ana Pérez', 'email' => 'ana@example.com', 'email_verified' => false]]);

    $user = User::query()->sole();

    expect($user->role)->toBe(UserRole::Customer)
        ->and($user->email_verified_at)->toBeNull();
    $this->assertAuthenticatedAs($user, 'customer');
});

it('never takes the role from the request', function () {
    $this->postJson(route('api.v1.auth.register'), registration(['role' => 'admin']))->assertCreated();

    expect(User::query()->sole()->role)->toBe(UserRole::Customer);
});

it('stores the email in lowercase', function () {
    $this->postJson(route('api.v1.auth.register'), registration(['email' => '  Ana@Example.COM ']))->assertCreated();

    expect(User::query()->sole()->email)->toBe('ana@example.com');
});

it('sends the verification email with a link to the storefront', function () {
    $this->postJson(route('api.v1.auth.register'), registration())->assertCreated();

    Notification::assertSentTo(User::query()->sole(), CustomerVerifyEmail::class,
        function (CustomerVerifyEmail $notification, array $channels, User $user): bool {
            return str_starts_with($notification->toMail($user)->actionUrl, 'http://storefront.test/cuenta/verificar-email?');
        });
});

it('rejects an email already registered, in any case', function () {
    User::factory()->create(['email' => 'ana@example.com']);

    $this->postJson(route('api.v1.auth.register'), registration(['email' => 'ANA@example.com']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'El correo electrónico ya está en uso.']);
});

it('requires a name, an email and a confirmed password', function () {
    $this->postJson(route('api.v1.auth.register'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password']);

    $this->postJson(route('api.v1.auth.register'), registration(['password_confirmation' => 'otra-cosa-distinta']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);

    expect(User::query()->count())->toBe(0);
});

it('limits each IP to 3 registrations per minute', function () {
    foreach (range(1, 3) as $attempt) {
        $this->postJson(route('api.v1.auth.register'), registration(['email' => "ana{$attempt}@example.com"]))->assertCreated();
    }

    $this->postJson(route('api.v1.auth.register'), registration(['email' => 'ana4@example.com']))->assertTooManyRequests();
});
