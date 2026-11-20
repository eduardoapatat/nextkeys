<?php

namespace Database\Factories;

use App\Enums\ProductKeyStatus;
use App\Models\Product;
use App\Models\ProductKey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductKey>
 */
class ProductKeyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'product_type' => fn (array $attributes) => Product::query()->whereKey($attributes['product_id'])->sole()->type,
            'code' => mb_strtoupper(fake()->unique()->bothify('?????-#####-?????')),
            'status' => ProductKeyStatus::Available,
        ];
    }

    public function reserved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductKeyStatus::Reserved,
        ]);
    }

    public function sold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductKeyStatus::Sold,
        ]);
    }
}
