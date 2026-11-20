<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database in one transaction: the catalog's
     * deferred constraint triggers check products together with their platforms.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            User::factory()->create([
                'name' => 'Test Customer',
                'email' => 'test@example.com',
            ]);

            $this->call([
                AdminUserSeeder::class,
                ActivationServiceSeeder::class,
                PlatformSeeder::class,
                RegionSeeder::class,
                CatalogDemoSeeder::class,
            ]);
        });
    }
}
