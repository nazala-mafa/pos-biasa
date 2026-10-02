<?php

use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->outlet = Team::factory()->create();
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->outlet->id);
});

test('seeder creates permissions only', function () {
    expect(Role::count())->toBe(0)
        ->and(Permission::count())->toBeGreaterThan(0);

    testRole('admin');
    testRole('cashier');

    expect(Role::findByName('admin')->hasPermissionTo('reports.view'))->toBeTrue()
        ->and(Role::findByName('cashier')->hasPermissionTo('sales.create'))->toBeTrue()
        ->and(Role::findByName('cashier')->hasPermissionTo('sales.refund'))->toBeFalse();
});

test('seeder is idempotent', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::count())->toBe(0);
});

test('user inherits permissions from assigned role', function () {
    $user = User::factory()->create()->assignRole(testRole('cashier'));

    expect($user->can('sales.create'))->toBeTrue()
        ->and($user->can('reports.view'))->toBeFalse();
});
