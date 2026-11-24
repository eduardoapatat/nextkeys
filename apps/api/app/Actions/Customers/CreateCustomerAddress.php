<?php

namespace App\Actions\Customers;

use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Saves a new shipping address in a customer's account, up to
 * `MAX_PER_CUSTOMER`. The customer's row is locked while counting, so two
 * requests at once cannot both take the last slot.
 */
final class CreateCustomerAddress
{
    public const int MAX_PER_CUSTOMER = 5;

    /**
     * @throws ValidationException
     */
    public function handle(User $customer, AddressData $address): CustomerAddress
    {
        $address = $address->validated();

        return DB::transaction(function () use ($customer, $address): CustomerAddress {
            User::query()->whereKey($customer->id)->lockForUpdate()->sole();

            if ($customer->addresses()->count() >= self::MAX_PER_CUSTOMER) {
                throw ValidationException::withMessages([
                    'address' => 'Puedes guardar hasta '.self::MAX_PER_CUSTOMER.' direcciones. Borra una para agregar otra.',
                ]);
            }

            return $customer->addresses()->create($address->toAttributes());
        });
    }
}
