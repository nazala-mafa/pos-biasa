<?php

use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TeamSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

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

test('superadmin can list outlets', function () {
    $this->actingAs(User::factory()->superadmin()->create())
        ->get(route('admin.outlets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/outlets/index')
            ->has('outlets'));
});

test('non superadmin users cannot list outlets', function () {
    $this->actingAs(User::factory()->create()->assignRole('admin'))
        ->get(route('admin.outlets.index'))
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

test('superadmin can list users with their outlet and role', function () {
    $outlet = Team::factory()->create(['name' => 'Cabang A']);
    $user = User::factory()->create();
    $outlet->memberships()->create(['user_id' => $user->id, 'role' => 'member']);
    app(PermissionRegistrar::class)->setPermissionsTeamId($outlet->id);
    $user->assignRole('cashier');

    $this->actingAs(User::factory()->superadmin()->create())
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/index')
            ->where('users', fn ($users) => collect($users)->contains(
                fn ($row) => $row['email'] === $user->email
                    && $row['username'] === $user->name
                    && $row['outlets'] === [['outlet' => 'Cabang A', 'roles' => ['cashier']]]
            )));
});

test('non superadmin users cannot list admin users', function () {
    $this->actingAs(User::factory()->create()->assignRole('admin'))
        ->get(route('admin.users.index'))
        ->assertForbidden();
});
