<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('stores a valid address', function () {
    insertCustomerAddressRow([]);

    expect(DB::table('customer_addresses')->count())->toBe(1);
});

it('rejects an unknown region', function () {
    expect(fn () => insertCustomerAddressRow(['region' => 'XX']))
        ->toThrow(QueryException::class, 'customer_addresses_region_check');
});

it('rejects a phone that is not normalized', function (string $phone) {
    expect(fn () => insertCustomerAddressRow(['phone' => $phone]))
        ->toThrow(QueryException::class, 'customer_addresses_phone_format');
})->with(['bare' => '912345678', 'spaced' => '+56 9 1234 5678', 'landline' => '+56221234567']);

it('rejects blank text fields', function (string $column) {
    expect(fn () => insertCustomerAddressRow([$column => '  ']))
        ->toThrow(QueryException::class, 'customer_addresses_text_not_blank');
})->with(['recipient_name', 'commune', 'street', 'street_number', 'apartment']);

/**
 * @param  array<string, mixed>  $overrides
 */
function insertCustomerAddressRow(array $overrides): void
{
    DB::table('customer_addresses')->insert([
        'user_id' => User::factory()->create()->id,
        'public_id' => (string) Str::ulid(),
        'recipient_name' => 'Ana Pérez',
        'phone' => '+56912345678',
        'region' => 'RM',
        'commune' => 'Ñuñoa',
        'street' => 'Av. Irarrázaval',
        'street_number' => '1234',
        'apartment' => null,
        ...$overrides,
    ]);
}
