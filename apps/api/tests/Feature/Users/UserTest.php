<?php

use App\Enums\UserRole;
use App\Models\User;

it('does not mass assign the role', function () {
    $user = User::query()->make([
        'name' => 'Mallory',
        'email' => 'mallory@example.com',
        'password' => 'password',
        'role' => UserRole::Admin,
    ]);

    expect($user->getAttributes())->not->toHaveKey('role');
});
