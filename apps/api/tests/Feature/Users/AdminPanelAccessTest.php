<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

it('grants the admin panel only to admins', function (UserRole $role, bool $allowed) {
    $user = User::factory()->create();
    $user->forceFill(['role' => $role])->save();

    expect(Gate::forUser($user)->allows('access-admin-panel'))->toBe($allowed);
})->with([
    'admin' => [UserRole::Admin, true],
    'customer' => [UserRole::Customer, false],
]);

it('forbids customers from the admin panel', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertForbidden();
});
