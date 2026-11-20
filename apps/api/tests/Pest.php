<?php

use App\Enums\ProductType;
use App\Models\ActivationService;
use App\Models\Platform;
use App\Models\Region;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Inserts a valid `digital_key` product row straight into the database,
 * bypassing models and Actions, plus its platforms. Returns its id.
 *
 * @param  array<string, mixed>  $overrides
 * @param  list<int>|null  $platformIds  one new platform when null
 */
function insertProductRow(array $overrides = [], ?array $platformIds = null): int
{
    $id = DB::table('products')->insertGetId([
        'title_id' => Title::factory()->create()->id,
        'type' => ProductType::DigitalKey->value,
        'slug' => fake()->unique()->slug(3),
        'activation_service_id' => ActivationService::factory()->create()->id,
        'region_id' => Region::factory()->create()->id,
        'price' => 19990,
        'stock' => null,
        'is_published' => false,
        'created_at' => now(),
        'updated_at' => now(),
        ...$overrides,
    ]);

    foreach ($platformIds ?? [Platform::factory()->create()->id] as $platformId) {
        DB::table('product_platforms')->insert(['product_id' => $id, 'platform_id' => $platformId]);
    }

    return $id;
}

/**
 * Runs the deferred constraint triggers now. RefreshDatabase never commits,
 * so without this an `INITIALLY DEFERRED` trigger would never fire in tests.
 * Constraints go back to deferred afterwards, as they are in production.
 */
function checkDeferredConstraints(): void
{
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
}
