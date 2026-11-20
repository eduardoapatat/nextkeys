<?php

use App\Models\Platform;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('rejects an invalid product row', function (array $overrides, string $constraint) {
    expect(fn () => insertProductRow($overrides))->toThrow(QueryException::class, $constraint);
})->with([
    'zero price' => [['price' => 0], 'products_price_positive'],
    'negative price' => [['price' => -1], 'products_price_positive'],
    'unknown type' => [['type' => 'gift_card'], 'products_type_check'],
    'unknown status' => [['status' => 'deleted'], 'products_status_check'],
    'slug with spaces' => [['slug' => 'starfall steam'], 'products_slug_format'],
    'uppercase slug' => [['slug' => 'Starfall-steam'], 'products_slug_format'],
    'slug with leading hyphen' => [['slug' => '-starfall'], 'products_slug_format'],
    'slug with trailing hyphen' => [['slug' => 'starfall-'], 'products_slug_format'],
    'slug with double hyphen' => [['slug' => 'starfall--steam'], 'products_slug_format'],
    'digital key without activation service' => [['activation_service_id' => null], 'products_key_types_require_service_and_region'],
    'digital key without region' => [['region_id' => null], 'products_key_types_require_service_and_region'],
    'license without activation service' => [['type' => 'license', 'activation_service_id' => null], 'products_key_types_require_service_and_region'],
    'license without region' => [['type' => 'license', 'region_id' => null], 'products_key_types_require_service_and_region'],
    'digital key with stock' => [['stock' => 5], 'products_stock_only_for_physical'],
    'license with stock' => [['type' => 'license', 'stock' => 5], 'products_stock_only_for_physical'],
    'physical without stock' => [['type' => 'physical', 'stock' => null], 'products_stock_only_for_physical'],
    'physical with negative stock' => [['type' => 'physical', 'stock' => -1], 'products_stock_non_negative'],
]);

it('accepts a physical product without activation service or region', function () {
    insertProductRow(['type' => 'physical', 'activation_service_id' => null, 'region_id' => null, 'stock' => 0]);

    expect(DB::table('products')->count())->toBe(1);
});

it('rejects a slug already used by another product', function () {
    insertProductRow(['slug' => 'starfall-steam-windows-global']);

    expect(fn () => insertProductRow(['slug' => 'starfall-steam-windows-global']))
        ->toThrow(QueryException::class, 'products_slug_unique');
});

it('rejects changing the type of a product', function () {
    $productId = insertProductRow();

    expect(fn () => DB::table('products')->where('id', $productId)->update(['type' => 'license']))
        ->toThrow(QueryException::class, 'products.type is immutable');
});

it('rejects deleting a referenced catalog row while a product uses it', function (string $table, string $column, string $constraint) {
    $platform = Platform::factory()->create();
    $productId = insertProductRow(platformIds: [$platform->id]);
    $referencedId = $column === 'platform_id'
        ? $platform->id
        : DB::table('products')->where('id', $productId)->value($column);

    expect(fn () => DB::table($table)->where('id', $referencedId)->delete())
        ->toThrow(QueryException::class, $constraint);
})->with([
    'title' => ['titles', 'title_id', 'products_title_id_foreign'],
    'activation service' => ['activation_services', 'activation_service_id', 'products_activation_service_id_foreign'],
    'region' => ['regions', 'region_id', 'products_region_id_foreign'],
    'platform' => ['platforms', 'platform_id', 'product_platforms_platform_id_foreign'],
]);
