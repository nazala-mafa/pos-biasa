<?php

use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create()->assignRole(testRole('admin'));
});

test('admin can list roles', function () {
    $this->actingAs($this->admin)
        ->get(route('roles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('roles/index')->has('roles', 1));
});

test('admin role receives user and role management permissions', function () {
    expect(Role::findByName('admin')->hasPermissionTo('roles.delete'))->toBeTrue()
        ->and(testRole('manager')->hasPermissionTo('users.view'))->toBeFalse();
});

test('non admins are forbidden', function () {
    $manager = User::factory()->create()->assignRole(testRole('manager'));

    $this->actingAs($manager)->get(route('roles.index'))->assertForbidden();
});

test('admin can create a role with permissions', function () {
    $this->actingAs($this->admin)
        ->post(route('roles.store'), ['name' => 'auditor', 'permissions' => ['reports.sales']])
        ->assertRedirect(route('roles.index'));

    expect(Role::findByName('auditor')->hasPermissionTo('reports.sales'))->toBeTrue();
});

test('role name must be unique', function () {
    testRole('cashier');

    $this->actingAs($this->admin)
        ->post(route('roles.store'), ['name' => 'cashier'])
        ->assertSessionHasErrors('name');
});

test('admin can update and delete a custom role', function () {
    $role = Role::create(['name' => 'temp', 'team_id' => $this->admin->current_team_id]);

    $this->actingAs($this->admin)
        ->put(route('roles.update', $role), ['name' => 'temp2', 'permissions' => ['sales.view']])
        ->assertRedirect(route('roles.index'));

    expect($role->refresh()->name)->toBe('temp2')
        ->and($role->hasPermissionTo('sales.view'))->toBeTrue();

    $this->actingAs($this->admin)->delete(route('roles.destroy', $role))->assertRedirect(route('roles.index'));
    $this->assertModelMissing($role);
});

test('admin role of the outlet can be modified and deleted', function () {
    $adminRole = Role::findByName('admin');

    $this->actingAs($this->admin)
        ->put(route('roles.update', $adminRole), ['name' => 'owner', 'permissions' => ['roles.view', 'roles.update', 'roles.delete']])
        ->assertRedirect(route('roles.index'));
    expect($adminRole->refresh()->name)->toBe('owner');

    $this->actingAs($this->admin)->delete(route('roles.destroy', $adminRole))->assertRedirect(route('roles.index'));
    $this->assertModelMissing($adminRole);
});

test('roles of other outlets are hidden and unmanageable', function () {
    $other = Role::create(['name' => 'other-outlet', 'team_id' => Team::factory()->create()->id]);
    URL::defaults(['current_team' => $this->admin->currentTeam->slug]);

    $this->actingAs($this->admin)
        ->get(route('roles.index'))
        ->assertInertia(fn (Assert $page) => $page->has('roles', 1));

    $this->actingAs($this->admin)->get(route('roles.edit', $other))->assertNotFound();
    $this->actingAs($this->admin)->delete(route('roles.destroy', $other))->assertNotFound();
});

test('created roles are bound to the current outlet', function () {
    $this->actingAs($this->admin)->post(route('roles.store'), ['name' => 'auditor']);

    expect(Role::where('name', 'auditor')->value('team_id'))->toBe($this->admin->current_team_id);
});

test('new outlets have no roles', function () {
    $other = Team::factory()->create();

    expect(Role::where('team_id', $other->id)->count())->toBe(0)
        ->and(Role::whereNull('team_id')->count())->toBe(0);
});

test('role form lists permissions grouped by sidebar group and menu in sidebar order', function () {
    $this->actingAs($this->admin)
        ->get(route('roles.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('roles/form')
            ->where('permissions.0.group', 'POS')
            ->where('permissions.0.menus.0.menu', 'sales')
            ->where('permissions.0.menus.0.permissions', ['sales.view', 'sales.create', 'sales.refund'])
            ->where('permissions', fn ($groups) => collect($groups)->pluck('group')->all() === ['POS', 'Reporting', 'Master Data', 'Administration']));
});

test('editing a role does not affect the same role of another outlet', function () {
    $other = Team::factory()->create();
    app(PermissionRegistrar::class)->setPermissionsTeamId($other->id);
    $otherCashier = testRole('cashier');
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->admin->current_team_id);

    $this->actingAs($this->admin)
        ->put(route('roles.update', testRole('cashier')), ['name' => 'cashier', 'permissions' => []])
        ->assertRedirect(route('roles.index'));

    expect($otherCashier->refresh()->hasPermissionTo('sales.create'))->toBeTrue();
});
