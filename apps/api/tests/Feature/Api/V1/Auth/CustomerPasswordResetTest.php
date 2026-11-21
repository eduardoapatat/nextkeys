<?php

use App\Models\User;
use App\Notifications\CustomerResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

beforeEach(function () {
    Notification::fake();
    $this->withHeaders(fromStorefront());
});

const RESET_LINK_SENT = 'Si el correo corresponde a una cuenta, te enviamos un enlace para restablecer la contraseña.';

it('sends a reset link to the storefront', function () {
    $customer = User::factory()->create(['email' => 'ana@example.com']);

    $this->postJson(route('api.v1.auth.password.email'), ['email' => 'ANA@example.com'])
        ->assertOk()
        ->assertExactJson(['message' => RESET_LINK_SENT]);

    Notification::assertSentTo($customer, CustomerResetPassword::class,
        function (CustomerResetPassword $notification, array $channels, User $user): bool {
            return str_starts_with($notification->toMail($user)->actionUrl, 'http://storefront.test/cuenta/restablecer?token=');
        });
});

it('answers the same for an unknown email', function () {
    $this->postJson(route('api.v1.auth.password.email'), ['email' => 'nobody@example.com'])
        ->assertOk()
        ->assertExactJson(['message' => RESET_LINK_SENT]);

    Notification::assertNothingSent();
});

it('resets the password with a valid token', function () {
    $customer = User::factory()->create(['email' => 'ana@example.com']);
    $token = Password::broker('customers')->createToken($customer);

    $this->postJson(route('api.v1.auth.password.update'), [
        'token' => $token,
        'email' => 'ana@example.com',
        'password' => 'una-clave-nueva-123',
        'password_confirmation' => 'una-clave-nueva-123',
    ])->assertNoContent();

    expect(Hash::check('una-clave-nueva-123', (string) $customer->fresh()?->password))->toBeTrue();
});

it('rejects an invalid token', function () {
    $customer = User::factory()->create(['email' => 'ana@example.com']);

    $this->postJson(route('api.v1.auth.password.update'), [
        'token' => 'not-a-token',
        'email' => 'ana@example.com',
        'password' => 'una-clave-nueva-123',
        'password_confirmation' => 'una-clave-nueva-123',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'El enlace para restablecer la contraseña no es válido o expiró.']);

    expect(Hash::check('password', (string) $customer->fresh()?->password))->toBeTrue();
});

it('limits each IP to 5 password requests per minute', function () {
    foreach (range(1, 5) as $attempt) {
        $this->postJson(route('api.v1.auth.password.email'), ['email' => "ana{$attempt}@example.com"])->assertOk();
    }

    $this->postJson(route('api.v1.auth.password.email'), ['email' => 'ana6@example.com'])->assertTooManyRequests();
});
