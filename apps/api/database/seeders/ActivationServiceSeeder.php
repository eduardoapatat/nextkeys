<?php

namespace Database\Seeders;

use App\Models\ActivationService;
use Illuminate\Database\Seeder;

class ActivationServiceSeeder extends Seeder
{
    /**
     * Seed the activation services buyers recognize from key stores. Idempotent: rows are matched by slug.
     */
    public function run(): void
    {
        ActivationService::query()->upsert([
            ['name' => 'Steam', 'slug' => 'steam'],
            ['name' => 'PlayStation Store', 'slug' => 'playstation-store'],
            ['name' => 'Nintendo eShop', 'slug' => 'nintendo-eshop'],
            ['name' => 'Xbox', 'slug' => 'xbox'],
            ['name' => 'Microsoft Store', 'slug' => 'microsoft-store'],
            ['name' => 'Microsoft Account', 'slug' => 'microsoft-account'],
        ], uniqueBy: ['slug'], update: ['name']);
    }
}
