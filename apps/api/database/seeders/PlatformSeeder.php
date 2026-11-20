<?php

namespace Database\Seeders;

use App\Models\Platform;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    /**
     * Seed the platforms (devices and operating systems). Idempotent: rows are matched by slug.
     */
    public function run(): void
    {
        Platform::query()->upsert([
            ['name' => 'Windows', 'slug' => 'windows'],
            ['name' => 'macOS', 'slug' => 'macos'],
            ['name' => 'PS4', 'slug' => 'ps4'],
            ['name' => 'PS5', 'slug' => 'ps5'],
            ['name' => 'Switch', 'slug' => 'switch'],
            ['name' => 'Switch 2', 'slug' => 'switch-2'],
            ['name' => 'Xbox One', 'slug' => 'xbox-one'],
            ['name' => 'Xbox Series', 'slug' => 'xbox-series'],
            ['name' => 'Android', 'slug' => 'android'],
            ['name' => 'iOS', 'slug' => 'ios'],
        ], uniqueBy: ['slug'], update: ['name']);
    }
}
