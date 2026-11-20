<?php

namespace Database\Factories;

use App\Enums\ProductKeyStatus;
use App\Models\Order;
use App\Models\OrderItem;
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

    /**
     * Reserved for a one-unit line of a new pending order: a reserved key
     * always belongs to an order line.
     */
    public function reserved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductKeyStatus::Reserved,
            'order_item_id' => fn (array $attributes) => OrderItem::factory()
                ->create(['product_id' => $attributes['product_id']])->id,
        ]);
    }

    /**
     * Sold through a one-unit line of a new completed order.
     */
    public function sold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductKeyStatus::Sold,
            'order_item_id' => fn (array $attributes) => OrderItem::factory()
                ->for(Order::factory()->completed())
                ->create(['product_id' => $attributes['product_id']])->id,
        ]);
    }
}
