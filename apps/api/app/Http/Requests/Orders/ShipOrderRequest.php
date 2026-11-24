<?php

namespace App\Http\Requests\Orders;

use App\Enums\Carrier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The courier and tracking number an admin enters when a parcel leaves.
 */
class ShipOrderRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'carrier' => ['required', Rule::enum(Carrier::class)],
            'tracking_number' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9-]{4,40}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tracking_number.regex' => 'El número de seguimiento solo lleva letras, números y guiones (4 a 40).',
        ];
    }

    public function carrier(): Carrier
    {
        return $this->enum('carrier', Carrier::class) ?? Carrier::Starken;
    }

    public function trackingNumber(): string
    {
        return $this->string('tracking_number')->trim()->toString();
    }
}
