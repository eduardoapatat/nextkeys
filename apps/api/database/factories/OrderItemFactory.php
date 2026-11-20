<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state: one unit. The price and the snapshot
     * are taken from the product in `configure()`, unless given.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'quantity' => 1,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (OrderItem $item): void {
            $product = Product::query()->with('title')->findOrFail($item->product_id);

            $item->fill([
                'unit_price' => $item->getAttribute('unit_price') ?? $product->price,
                'title_name' => $item->getAttribute('title_name') ?? $product->title->name,
                'product_type' => $item->getAttribute('product_type') ?? $product->type,
            ]);
            $item->fill([
                'line_total' => $item->getAttribute('line_total') ?? $item->unit_price * $item->quantity,
            ]);
        });
    }
}
