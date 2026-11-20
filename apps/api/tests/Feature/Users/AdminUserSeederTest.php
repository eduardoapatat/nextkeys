<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    config([
        'admin.initial.name' => 'Root',
        'admin.initial.email' => 'root@nextkeys.test',
        'admin.initial.password' => 'initial-password',
    ]);
});

it('creates a verified admin with the configured credentials', function () {
    $this->seed(AdminUserSeeder::class);

    $admin = User::query()->sole();

    expect($admin->name)->toBe('Root')
        ->and($admin->email)->toBe('root@nextkeys.test')
        ->and($admin->role)->toBe(UserRole::Admin)
        ->and($admin->email_verified_at)->not->toBeNull()
        ->and(Hash::check('initial-password', $admin->password))->toBeTrue();
});

it('leaves an existing admin and its password untouched when seeded again', function () {
    $this->seed(AdminUserSeeder::class);
    config(['admin.initial.password' => 'another-password']);

    $this->seed(AdminUserSeeder::class);

    expect(User::query()->count())->toBe(1)
        ->and(Hash::check('initial-password', User::query()->sole()->password))->toBeTrue();
});

it('refuses to seed without credentials', function (string $key) {
    config([$key => null]);

    expect(fn () => $this->seed(AdminUserSeeder::class))
        ->toThrow(RuntimeException::class, 'Set ADMIN_EMAIL and ADMIN_PASSWORD in .env');

    expect(User::query()->count())->toBe(0);
})->with(['admin.initial.email', 'admin.initial.password']);
