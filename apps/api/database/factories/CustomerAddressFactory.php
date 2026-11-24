<?php

namespace Database\Factories;

use App\Enums\ChileRegion;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    /**
     * Define the model's default state: an address in Santiago.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'recipient_name' => fake()->name(),
            'phone' => '+569'.fake()->numerify('########'),
            'region' => ChileRegion::Metropolitana,
            'commune' => fake()->randomElement(ChileRegion::Metropolitana->communes()),
            'street' => fake()->streetName(),
            'street_number' => (string) fake()->numberBetween(1, 9999),
            'apartment' => null,
        ];
    }
}
