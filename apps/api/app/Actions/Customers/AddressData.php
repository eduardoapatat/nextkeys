<?php

namespace App\Actions\Customers;

use App\Enums\ChileRegion;
use Illuminate\Validation\ValidationException;

/**
 * A shipping address as the customer typed it. `validated()` applies the
 * rules the database cannot see (the commune belongs to the region) and
 * normalizes the phone to the `customer_addresses_phone_format` CHECK.
 */
final readonly class AddressData
{
    public function __construct(
        public string $recipientName,
        public string $phone,
        public ChileRegion $region,
        public string $commune,
        public string $street,
        public string $streetNumber,
        public ?string $apartment = null,
    ) {}

    /**
     * @throws ValidationException
     */
    public function validated(): self
    {
        $errors = [];
        $phone = self::normalizePhone($this->phone);

        if ($phone === null) {
            $errors['phone'] = 'Ingresa un celular chileno, por ejemplo +56 9 1234 5678.';
        }

        if (! $this->region->hasCommune($this->commune)) {
            $errors['commune'] = 'Elige una comuna de la región seleccionada.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $apartment = $this->apartment === null ? null : trim($this->apartment);

        return new self(
            recipientName: trim($this->recipientName),
            phone: (string) $phone,
            region: $this->region,
            commune: $this->commune,
            street: trim($this->street),
            streetNumber: trim($this->streetNumber),
            apartment: $apartment === '' ? null : $apartment,
        );
    }

    /**
     * A Chilean mobile number as `+569XXXXXXXX`, or null when it is not one.
     * Accepts spaces, dashes, dots and parentheses, with or without `+56`.
     */
    public static function normalizePhone(string $phone): ?string
    {
        $digits = (string) preg_replace('/[\s\-.()]/', '', $phone);

        if (preg_match('/^(?:\+?56)?(9\d{8})$/', $digits, $matches) !== 1) {
            return null;
        }

        return '+56'.$matches[1];
    }

    /**
     * @return array{recipient_name: string, phone: string, region: ChileRegion, commune: string, street: string, street_number: string, apartment: string|null}
     */
    public function toAttributes(): array
    {
        return [
            'recipient_name' => $this->recipientName,
            'phone' => $this->phone,
            'region' => $this->region,
            'commune' => $this->commune,
            'street' => $this->street,
            'street_number' => $this->streetNumber,
            'apartment' => $this->apartment,
        ];
    }
}
