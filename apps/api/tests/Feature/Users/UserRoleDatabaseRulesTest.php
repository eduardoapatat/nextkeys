<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('rejects a user with an unknown role', function () {
    expect(fn () => insertUserRow(['role' => 'superuser']))
        ->toThrow(QueryException::class, 'users_role_check');
});

it('rejects a user without a role', function () {
    expect(fn () => insertUserRow(['role' => null]))
        ->toThrow(QueryException::class, 'null value in column "role"');
});

/**
 * @param  array<string, mixed>  $overrides
 */
function insertUserRow(array $overrides): void
{
    DB::table('users')->insert([
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'password' => 'secret',
        'role' => 'customer',
        ...$overrides,
    ]);
}
