<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create()->assignRole('admin');
});

test('admin can list roles', function () {
    $this->actingAs($this->admin)
        ->get(route('roles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('roles/index')->has('roles', 3));
});

test('admin role receives user and role management permissions', function () {
    expect(Role::findByName('admin')->hasPermissionTo('roles.delete'))->toBeTrue()
        ->and(Role::findByName('manager')->hasPermissionTo('users.view'))->toBeFalse();
});

test('non admins are forbidden', function () {
    $manager = User::factory()->create()->assignRole('manager');

    $this->actingAs($manager)->get(route('roles.index'))->assertForbidden();
});

test('admin can create a role with permissions', function () {
    $this->actingAs($this->admin)
        ->post(route('roles.store'), ['name' => 'auditor', 'permissions' => ['reports.view']])
        ->assertRedirect(route('roles.index'));

    expect(Role::findByName('auditor')->hasPermissionTo('reports.view'))->toBeTrue();
});

test('role name must be unique', function () {
    $this->actingAs($this->admin)
        ->post(route('roles.store'), ['name' => 'cashier'])
        ->assertSessionHasErrors('name');
});

test('admin can update and delete a custom role', function () {
    $role = Role::create(['name' => 'temp']);

    $this->actingAs($this->admin)
        ->put(route('roles.update', $role), ['name' => 'temp2', 'permissions' => ['sales.view']])
        ->assertRedirect(route('roles.index'));

    expect($role->refresh()->name)->toBe('temp2')
        ->and($role->hasPermissionTo('sales.view'))->toBeTrue();

    $this->actingAs($this->admin)->delete(route('roles.destroy', $role))->assertRedirect(route('roles.index'));
    $this->assertModelMissing($role);
});

test('admin role cannot be modified or deleted', function () {
    $adminRole = Role::findByName('admin');

    $this->actingAs($this->admin)
        ->put(route('roles.update', $adminRole), ['name' => 'admin', 'permissions' => []])
        ->assertForbidden();
    $this->actingAs($this->admin)->delete(route('roles.destroy', $adminRole))->assertForbidden();
});
