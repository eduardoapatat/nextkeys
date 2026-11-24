<?php

namespace App\Http\Requests\Api\V1\Account;

use App\Actions\Customers\AddressData;
use App\Enums\ChileRegion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A shipping address from the storefront account. The commune and the phone
 * are checked by `AddressData::validated()` in the Action.
 */
class SaveCustomerAddressRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'recipient_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'region' => ['required', Rule::enum(ChileRegion::class)],
            'commune' => ['required', 'string', 'max:60'],
            'street' => ['required', 'string', 'max:120'],
            'street_number' => ['required', 'string', 'max:20'],
            'apartment' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function address(): AddressData
    {
        return new AddressData(
            recipientName: $this->string('recipient_name')->toString(),
            phone: $this->string('phone')->toString(),
            region: $this->enum('region', ChileRegion::class) ?? ChileRegion::Metropolitana,
            commune: $this->string('commune')->toString(),
            street: $this->string('street')->toString(),
            streetNumber: $this->string('street_number')->toString(),
            apartment: $this->filled('apartment') ? $this->string('apartment')->toString() : null,
        );
    }
}
