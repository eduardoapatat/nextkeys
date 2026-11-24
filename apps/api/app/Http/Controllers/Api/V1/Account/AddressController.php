<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Actions\Customers\CreateCustomerAddress;
use App\Actions\Customers\DeleteCustomerAddress;
use App\Actions\Customers\UpdateCustomerAddress;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\SaveCustomerAddressRequest;
use App\Http\Resources\Api\V1\AddressResource;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * The signed in customer's shipping addresses. Another customer's address is
 * a 404, like one that does not exist.
 */
class AddressController extends Controller
{
    public function index(#[CurrentUser] User $customer): AnonymousResourceCollection
    {
        return AddressResource::collection($customer->addresses()->oldest('id')->get());
    }

    public function store(SaveCustomerAddressRequest $request, #[CurrentUser] User $customer, CreateCustomerAddress $createAddress): JsonResponse
    {
        $address = $createAddress->handle($customer, $request->address());

        return (new AddressResource($address))->response()->setStatusCode(201);
    }

    public function update(SaveCustomerAddressRequest $request, #[CurrentUser] User $customer, string $publicId, UpdateCustomerAddress $updateAddress): AddressResource
    {
        return new AddressResource($updateAddress->handle(self::find($customer, $publicId), $request->address()));
    }

    public function destroy(#[CurrentUser] User $customer, string $publicId, DeleteCustomerAddress $deleteAddress): Response
    {
        $deleteAddress->handle(self::find($customer, $publicId));

        return response()->noContent();
    }

    /**
     * The customer's own address by its public id, or a 404.
     */
    public static function find(User $customer, string $publicId): CustomerAddress
    {
        return $customer->addresses()->where('public_id', $publicId)->firstOrFail();
    }
}
