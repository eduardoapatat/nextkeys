<?php

namespace App\Actions\Customers;

use App\Models\CustomerAddress;

/**
 * Deletes a saved shipping address for good: orders keep their own copy.
 */
final class DeleteCustomerAddress
{
    public function handle(CustomerAddress $customerAddress): void
    {
        $customerAddress->delete();
    }
}
