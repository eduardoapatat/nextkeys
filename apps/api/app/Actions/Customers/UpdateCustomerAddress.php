<?php

namespace App\Actions\Customers;

use App\Models\CustomerAddress;
use Illuminate\Validation\ValidationException;

/**
 * Changes a saved shipping address. Orders placed with it keep their own
 * copy, so they are not affected.
 */
final class UpdateCustomerAddress
{
    /**
     * @throws ValidationException
     */
    public function handle(CustomerAddress $customerAddress, AddressData $address): CustomerAddress
    {
        $customerAddress->update($address->validated()->toAttributes());

        return $customerAddress;
    }
}
