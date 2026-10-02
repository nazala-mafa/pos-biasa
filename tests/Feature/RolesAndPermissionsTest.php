<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('seeder creates roles with their permissions', function () {
    expect(Role::findByName('admin')->hasPermissionTo('reports.view'))->toBeTrue()
        ->and(Role::findByName('cashier')->hasPermissionTo('sales.create'))->toBeTrue()
        ->and(Role::findByName('cashier')->hasPermissionTo('sales.refund'))->toBeFalse();
});

test('seeder is idempotent', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::count())->toBe(3);
});

test('user inherits permissions from assigned role', function () {
    $user = User::factory()->create()->assignRole('cashier');

    expect($user->can('sales.create'))->toBeTrue()
        ->and($user->can('reports.view'))->toBeFalse();
});
