<?php

namespace Database\Factories;

use App\Enums\Carrier;
use App\Enums\ShipmentStatus;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    /**
     * Define the model's default state: a shipment to pack, with the snapshot
     * of a factory address.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            ...Shipment::attributesFrom(CustomerAddress::factory()->make()),
        ];
    }

    public function shipped(): static
    {
        return $this->state(fn (): array => [
            'status' => ShipmentStatus::Shipped,
            'carrier' => Carrier::Starken,
            'tracking_number' => fake()->numerify('##########'),
            'shipped_at' => now(),
        ]);
    }
}
