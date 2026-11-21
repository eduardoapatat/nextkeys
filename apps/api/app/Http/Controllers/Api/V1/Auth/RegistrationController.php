<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Customers\RegisterCustomer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterCustomerRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RegistrationController extends Controller
{
    public function __invoke(RegisterCustomerRequest $request, RegisterCustomer $registerCustomer): JsonResponse
    {
        $customer = $registerCustomer->handle(
            $request->string('name')->value(),
            $request->string('email')->value(),
            $request->string('password')->value(),
        );

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return (new CustomerResource($customer))->response()->setStatusCode(201);
    }
}
