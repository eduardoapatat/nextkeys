<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Concerns\NormalizesEmail;
use App\Concerns\PasswordValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ResetCustomerPasswordRequest extends FormRequest
{
    use NormalizesEmail, PasswordValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => $this->passwordRules(),
        ];
    }
}
