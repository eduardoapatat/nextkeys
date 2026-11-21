<?php

namespace App\Actions\Customers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Verified;

/**
 * Marks a customer's email as verified from a signed link (the signature is
 * checked by the route). Idempotent: a link already used changes nothing.
 */
final class VerifyCustomerEmail
{
    /**
     * @throws AuthorizationException when the hash is not the current email's
     */
    public function handle(int $userId, string $hash): void
    {
        $customer = User::query()->where('role', UserRole::Customer)->findOrFail($userId);

        if (! hash_equals(sha1($customer->email), $hash)) {
            throw new AuthorizationException;
        }

        if ($customer->hasVerifiedEmail()) {
            return;
        }

        $customer->markEmailAsVerified();

        event(new Verified($customer));
    }
}
