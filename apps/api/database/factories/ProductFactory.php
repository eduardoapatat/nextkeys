<?php

namespace Database\Factories;

use App\Enums\ProductType;
use App\Models\ActivationService;
use App\Models\Platform;
use App\Models\Product;
use App\Models\Region;
use App\Models\Title;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Products must be written together with their platforms in one transaction:
 * the identity constraint trigger runs at commit. Tests already run inside one.
 *
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title_id' => Title::factory(),
            'type' => ProductType::DigitalKey,
            'slug' => fake()->unique()->slug(3),
            'activation_service_id' => ActivationService::factory(),
            'region_id' => Region::factory(),
            'price' => fake()->numberBetween(1, 100) * 1000 - 10,
            'stock' => null,
            'is_published' => false,
        ];
    }

    /**
     * Configure the model factory: every product gets one platform unless
     * platforms were given with withPlatforms().
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Product $product): void {
            if (! $product->platforms()->exists()) {
                $product->platforms()->attach(Platform::factory()->create());
            }
        });
    }

    public function license(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::License,
        ]);
    }

    /**
     * A physical copy with no activation service (full disc or cartridge).
     */
    public function physical(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::Physical,
            'activation_service_id' => null,
            'stock' => fake()->numberBetween(0, 20),
        ]);
    }

    /**
     * A physical box that only contains a download code for an activation service.
     */
    public function codeInBox(): static
    {
        return $this->physical()->state(fn (array $attributes) => [
            'activation_service_id' => ActivationService::factory(),
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
        ]);
    }

    public function withPlatforms(Platform ...$platforms): static
    {
        return $this->hasAttached($platforms, [], 'platforms');
    }
}
