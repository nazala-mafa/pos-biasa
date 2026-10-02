<?php

use App\Actions\Teams\AssignDefaultTeam;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->superadmin()->create();
    $this->defaultOutlet = Team::create(['name' => AssignDefaultTeam::NAME]);
});

test('superadmin can list outlets without personal teams', function () {
    Team::factory()->personal()->create();
    Team::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.outlets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/outlets/index')->has('outlets', 2));
});

test('non superadmin users cannot manage outlets, even with the admin role', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $outlet = Team::factory()->create();

    $this->actingAs($admin)->get(route('admin.outlets.index'))->assertForbidden();
    $this->actingAs($admin)->post(route('admin.outlets.store'), ['name' => 'X'])->assertForbidden();
    $this->actingAs($admin)->put(route('admin.outlets.update', $outlet), ['name' => 'Y'])->assertForbidden();
    $this->actingAs($admin)->delete(route('admin.outlets.destroy', $outlet))->assertForbidden();
});

test('superadmin can create an outlet', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.outlets.store'), ['name' => 'Cabang Bandung'])
        ->assertRedirect(route('admin.outlets.index'));

    expect(Team::where('name', 'Cabang Bandung')->where('is_personal', false)->exists())->toBeTrue();
});

test('outlet name is required, unique and not reserved', function () {
    $this->actingAs($this->admin)->post(route('admin.outlets.store'), ['name' => ''])->assertSessionHasErrors('name');
    $this->actingAs($this->admin)->post(route('admin.outlets.store'), ['name' => AssignDefaultTeam::NAME])->assertSessionHasErrors('name');
    $this->actingAs($this->admin)->post(route('admin.outlets.store'), ['name' => 'admin'])->assertSessionHasErrors('name');
});

test('superadmin can update an outlet', function () {
    $outlet = Team::factory()->create();

    $this->actingAs($this->admin)
        ->put(route('admin.outlets.update', $outlet), ['name' => 'Renamed'])
        ->assertRedirect(route('admin.outlets.index'));

    expect($outlet->refresh()->name)->toBe('Renamed');
});

test('superadmin can delete an empty outlet', function () {
    $outlet = Team::factory()->create();

    $this->actingAs($this->admin)->delete(route('admin.outlets.destroy', $outlet))->assertRedirect(route('admin.outlets.index'));

    $this->assertSoftDeleted($outlet);
});

test('outlet with users cannot be deleted', function () {
    $outlet = Team::factory()->create();
    $outlet->memberships()->create(['user_id' => $this->admin->id, 'role' => 'member']);

    $this->actingAs($this->admin)->delete(route('admin.outlets.destroy', $outlet))->assertRedirect(route('admin.outlets.index'));

    $this->assertNotSoftDeleted($outlet);
});

test('default outlet cannot be modified or deleted', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.outlets.update', $this->defaultOutlet), ['name' => 'Other'])
        ->assertForbidden();
    $this->actingAs($this->admin)->delete(route('admin.outlets.destroy', $this->defaultOutlet))->assertForbidden();
});

test('superadmin can open the dashboard of an outlet they are not a member of', function () {
    $outlet = Team::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('dashboard', $outlet))
        ->assertOk();

    expect($this->admin->refresh()->current_team_id)->toBe($outlet->id);
});
