<?php

use App\Actions\Catalog\ProductData;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\ActivationService;
use App\Models\Order;
use App\Models\Platform;
use App\Models\Region;
use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Assert;
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
        'status' => 'draft',
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
 * Inserts a key row straight into the database, bypassing the model. Returns
 * its id.
 *
 * @param  array<string, mixed>  $overrides
 */
function insertProductKeyRow(int $productId, array $overrides = []): int
{
    return DB::table('product_keys')->insertGetId([
        'product_id' => $productId,
        'product_type' => 'digital_key',
        'code' => 'not-really-encrypted',
        'code_hash' => hash('sha256', fake()->unique()->uuid()),
        'code_hint' => 'XXXXX-XXXXX-XXXXX',
        'status' => 'available',
        'created_at' => now(),
        'updated_at' => now(),
        ...$overrides,
    ]);
}

/**
 * Inserts a valid pending order row straight into the database, for a new
 * customer. Returns its id.
 *
 * @param  array<string, mixed>  $overrides
 */
function insertOrderRow(array $overrides = []): int
{
    return DB::table('orders')->insertGetId([
        'user_id' => User::factory()->create()->id,
        'reference' => Order::generateReference(),
        'status' => 'pending',
        'total' => 19990,
        'expires_at' => now()->addMinutes(15),
        'created_at' => now(),
        'updated_at' => now(),
        ...$overrides,
    ]);
}

/**
 * Inserts a valid one-unit order line row straight into the database.
 * Returns its id.
 *
 * @param  array<string, mixed>  $overrides
 */
function insertOrderItemRow(int $orderId, int $productId, array $overrides = []): int
{
    return DB::table('order_items')->insertGetId([
        'order_id' => $orderId,
        'product_id' => $productId,
        'quantity' => 1,
        'unit_price' => 19990,
        'line_total' => 19990,
        'title_name' => 'Hollow Knight',
        'product_type' => 'digital_key',
        'created_at' => now(),
        'updated_at' => now(),
        ...$overrides,
    ]);
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

/**
 * A valid `digital_key` ProductData with new related rows; override any field
 * by its constructor name (`stock: 5`, `platformIds: [...]`).
 */
function productData(mixed ...$overrides): ProductData
{
    return new ProductData(...[
        'titleId' => Title::factory()->create()->id,
        'activationServiceId' => ActivationService::factory()->create()->id,
        'regionId' => Region::factory()->create()->id,
        'platformIds' => [Platform::factory()->create()->id],
        'price' => 19990,
        'stock' => null,
        'status' => ProductStatus::Draft,
        'slug' => null,
        ...$overrides,
    ]);
}

/**
 * The validation errors an action throws, by field. Fails the test when the
 * action does not throw a ValidationException.
 *
 * @return array<string, list<string>>
 */
function validationErrorsOf(Closure $action): array
{
    try {
        $action();
    } catch (ValidationException $exception) {
        return $exception->errors();
    }

    Assert::fail('Expected a ValidationException, none was thrown.');
}
