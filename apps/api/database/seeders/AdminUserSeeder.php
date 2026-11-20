<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the initial admin from `config/admin.php`. If a user with that
     * email already exists it is left untouched, so re-seeding never resets
     * the admin's password.
     */
    public function run(): void
    {
        $email = Config::get('admin.initial.email');
        $password = Config::get('admin.initial.password');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            throw new RuntimeException('Set ADMIN_EMAIL and ADMIN_PASSWORD in .env to seed the initial admin.');
        }

        $admin = User::query()->firstOrNew(['email' => $email]);

        if ($admin->exists) {
            return;
        }

        $admin->fill([
            'name' => Config::string('admin.initial.name'),
            'password' => $password,
        ])->forceFill([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ])->save();
    }
}
