<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Customers\ResetCustomerPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotCustomerPasswordRequest;
use App\Http\Requests\Api\V1\Auth\ResetCustomerPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    /**
     * Always the same answer, whether the email belongs to a customer or not.
     */
    public function sendLink(ForgotCustomerPasswordRequest $request): JsonResponse
    {
        Password::broker('customers')->sendResetLink(['email' => $request->string('email')->value()]);

        return response()->json(['message' => 'Si el correo corresponde a una cuenta, te enviamos un enlace para restablecer la contraseña.']);
    }

    public function reset(ResetCustomerPasswordRequest $request, ResetCustomerPassword $resetCustomerPassword): Response
    {
        $resetCustomerPassword->handle(
            $request->string('token')->value(),
            $request->string('email')->value(),
            $request->string('password')->value(),
        );

        return response()->noContent();
    }
}
