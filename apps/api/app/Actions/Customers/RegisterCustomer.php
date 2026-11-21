<?php

namespace App\Actions\Customers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\Registered;

/**
 * Creates a storefront customer. The role is always `customer` and never
 * comes from the request; `Registered` sends the verification email.
 */
final class RegisterCustomer
{
    public function handle(string $name, string $email, #[\SensitiveParameter] string $password): User
    {
        $user = new User;
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => UserRole::Customer,
        ])->save();

        event(new Registered($user));

        return $user;
    }
}
