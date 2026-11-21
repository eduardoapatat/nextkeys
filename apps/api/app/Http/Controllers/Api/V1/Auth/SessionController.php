<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Customers\AuthenticateCustomer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginCustomerRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    public function show(#[CurrentUser] User $customer): CustomerResource
    {
        return new CustomerResource($customer);
    }

    public function store(LoginCustomerRequest $request, AuthenticateCustomer $authenticateCustomer): CustomerResource
    {
        $customer = $authenticateCustomer->handle(
            $request->string('email')->value(),
            $request->string('password')->value(),
            $request->boolean('remember'),
        );

        $request->session()->regenerate();

        return new CustomerResource($customer);
    }

    /**
     * The panel shares the session cookie, so this also signs an admin out of
     * the panel in the same browser (docs/domain/users.md).
     */
    public function destroy(Request $request): Response
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
