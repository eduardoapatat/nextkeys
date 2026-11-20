<?php

use App\Models\ActivationService;
use App\Models\Platform;
use App\Models\Region;
use App\Models\Title;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Columns shared by two products so they only differ in what each test changes.
 *
 * @return array<string, int|string>
 */
function sharedIdentity(): array
{
    return [
        'title_id' => Title::factory()->create()->id,
        'activation_service_id' => ActivationService::factory()->create()->id,
        'region_id' => Region::factory()->create()->id,
    ];
}

it('rejects a second product with the same identity', function () {
    $identity = sharedIdentity();
    $ps5 = Platform::factory()->create()->id;
    insertProductRow($identity, [$ps5]);
    insertProductRow($identity, [$ps5]);

    expect(fn () => checkDeferredConstraints())
        ->toThrow(UniqueConstraintViolationException::class, 'duplicate product identity');
});

it('treats null activation services as equal when comparing identities', function () {
    $identity = [...sharedIdentity(), 'type' => 'physical', 'activation_service_id' => null, 'stock' => 5];
    $ps5 = Platform::factory()->create()->id;
    insertProductRow($identity, [$ps5]);
    insertProductRow($identity, [$ps5]);

    expect(fn () => checkDeferredConstraints())
        ->toThrow(UniqueConstraintViolationException::class, 'duplicate product identity');
});

it('accepts products whose platform sets differ', function () {
    $identity = sharedIdentity();
    [$ps4, $ps5] = Platform::factory()->count(2)->create()->modelKeys();
    insertProductRow($identity, [$ps5]);
    insertProductRow($identity, [$ps4, $ps5]);

    checkDeferredConstraints();

    expect(DB::table('products')->count())->toBe(2);
});

it('accepts products that differ only in type', function () {
    $identity = sharedIdentity();
    $ps5 = Platform::factory()->create()->id;
    insertProductRow($identity, [$ps5]);
    insertProductRow([...$identity, 'type' => 'physical', 'stock' => 12], [$ps5]);

    checkDeferredConstraints();

    expect(DB::table('products')->count())->toBe(2);
});

it('rejects a product without platforms', function () {
    insertProductRow(platformIds: []);

    expect(fn () => checkDeferredConstraints())
        ->toThrow(QueryException::class, 'must cover at least one platform');
});

it('rejects a physical product with more than one platform', function () {
    insertProductRow(
        ['type' => 'physical', 'stock' => 5],
        Platform::factory()->count(2)->create()->modelKeys(),
    );

    expect(fn () => checkDeferredConstraints())
        ->toThrow(QueryException::class, 'physical product must cover exactly one platform');
});

it('rejects removing the last platform of a product', function () {
    $productId = insertProductRow();
    checkDeferredConstraints();

    DB::table('product_platforms')->where('product_id', $productId)->delete();

    expect(fn () => checkDeferredConstraints())
        ->toThrow(QueryException::class, 'must cover at least one platform');
});

it('rejects changing the region of a product into an existing identity', function () {
    $identity = sharedIdentity();
    $ps5 = Platform::factory()->create()->id;
    insertProductRow($identity, [$ps5]);
    $otherProductId = insertProductRow([...$identity, 'region_id' => Region::factory()->create()->id], [$ps5]);
    checkDeferredConstraints();

    DB::table('products')->where('id', $otherProductId)->update(['region_id' => $identity['region_id']]);

    expect(fn () => checkDeferredConstraints())
        ->toThrow(UniqueConstraintViolationException::class, 'duplicate product identity');
});

it('rejects changing a platform of a product into an existing identity', function () {
    $identity = sharedIdentity();
    [$ps4, $ps5] = Platform::factory()->count(2)->create()->modelKeys();
    insertProductRow($identity, [$ps5]);
    $otherProductId = insertProductRow($identity, [$ps4]);
    checkDeferredConstraints();

    DB::table('product_platforms')->where('product_id', $otherProductId)->update(['platform_id' => $ps5]);

    expect(fn () => checkDeferredConstraints())
        ->toThrow(UniqueConstraintViolationException::class, 'duplicate product identity');
});

it('accepts deleting a product together with its platforms', function () {
    $productId = insertProductRow();
    checkDeferredConstraints();

    DB::table('products')->where('id', $productId)->delete();
    checkDeferredConstraints();

    expect(DB::table('product_platforms')->count())->toBe(0);
});
