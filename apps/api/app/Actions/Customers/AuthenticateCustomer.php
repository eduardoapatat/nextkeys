<?php

namespace App\Actions\Customers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use LogicException;

/**
 * Signs a customer in on the `customer` guard. Its provider only finds
 * customers, so an admin's credentials fail like unknown ones.
 */
final class AuthenticateCustomer
{
    /**
     * @throws ValidationException
     */
    public function handle(string $email, #[\SensitiveParameter] string $password, bool $remember): User
    {
        $guard = Auth::guard('customer');

        if (! $guard->attempt(['email' => $email, 'password' => $password], $remember)) {
            throw ValidationException::withMessages(['email' => 'Estas credenciales no son válidas.']);
        }

        $user = $guard->user();

        return $user instanceof User ? $user : throw new LogicException('The customer guard returned no user after signing in.');
    }
}
