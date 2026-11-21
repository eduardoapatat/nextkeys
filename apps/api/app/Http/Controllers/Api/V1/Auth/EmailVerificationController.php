<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Customers\VerifyCustomerEmail;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Response;

class EmailVerificationController extends Controller
{
    public function send(#[CurrentUser] User $customer): Response
    {
        if ($customer->hasVerifiedEmail()) {
            return response()->noContent();
        }

        $customer->sendEmailVerificationNotification();

        return response()->noContent(202);
    }

    public function verify(int $id, string $hash, VerifyCustomerEmail $verifyCustomerEmail): Response
    {
        $verifyCustomerEmail->handle($id, $hash);

        return response()->noContent();
    }
}
