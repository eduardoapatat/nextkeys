<?php

namespace App\Actions\Customers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sets a new password from a reset link, through the `customers` broker: an
 * admin's token never works here.
 */
final class ResetCustomerPassword
{
    /**
     * @throws ValidationException when the token is invalid or expired
     */
    public function handle(#[\SensitiveParameter] string $token, string $email, #[\SensitiveParameter] string $password): void
    {
        $status = Password::broker('customers')->reset(
            ['token' => $token, 'email' => $email, 'password' => $password],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'El enlace para restablecer la contraseña no es válido o expiró.']);
        }
    }
}
