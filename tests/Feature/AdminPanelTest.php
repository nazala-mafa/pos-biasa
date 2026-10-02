<?php

use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TeamSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('superadmin can open the admin panel', function () {
    $this->actingAs(User::factory()->superadmin()->create())
        ->get('/admin')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->has('stats.users'));
});

test('non superadmin users are forbidden, even with the admin role', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)->get('/admin')->assertForbidden();
});

test('guests are redirected to login', function () {
    $this->get('/admin')->assertRedirect(route('login'));
});

test('superadmin can list teams', function () {
    $this->actingAs(User::factory()->superadmin()->create())
        ->get(route('admin.teams.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/teams/index')
            ->has('teams'));
});

test('non superadmin users cannot list teams', function () {
    $this->actingAs(User::factory()->create()->assignRole('admin'))
        ->get(route('admin.teams.index'))
        ->assertForbidden();
});

test('database seeder creates the default POS team with the seeded superadmin', function () {
    $this->seed();
    $this->seed(TeamSeeder::class);

    $team = Team::where('name', 'POS')->sole();

    expect($team->is_personal)->toBeFalse()
        ->and($team->members()->count())->toBe(1)
        ->and(User::where('email', 'superadmin@example.com')->first()->currentTeam->is($team))->toBeTrue();
});
