<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Seed the regions. Idempotent: rows are matched by slug.
     */
    public function run(): void
    {
        Region::query()->upsert([
            ['name' => 'Global', 'slug' => 'global'],
            ['name' => 'Europe', 'slug' => 'europe'],
            ['name' => 'LATAM', 'slug' => 'latam'],
            ['name' => 'North America', 'slug' => 'north-america'],
        ], uniqueBy: ['slug'], update: ['name']);
    }
}
