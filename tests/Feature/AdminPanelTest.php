<?php

use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
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
    $admin = User::factory()->create()->assignRole(testRole('admin'));

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
    $this->actingAs(User::factory()->create()->assignRole(testRole('admin')))
        ->get(route('admin.outlets.index'))
        ->assertForbidden();
});

test('database seeder creates the default POS team with the seeded superadmin', function () {
    $this->seed();
    $this->seed(TeamSeeder::class);

    $team = Team::where('name', 'POS')->sole();

    expect($team->members()->count())->toBe(1)
        ->and(User::where('email', 'superadmin@example.com')->first()->currentTeam->is($team))->toBeTrue();
});

test('superadmin can list users with their outlet and role', function () {
    $outlet = Team::factory()->create(['name' => 'Cabang A']);
    $user = User::factory()->create();
    $outlet->memberships()->create(['user_id' => $user->id, 'role' => 'member']);
    app(PermissionRegistrar::class)->setPermissionsTeamId($outlet->id);
    $user->assignRole(testRole('cashier'));

    $this->actingAs(User::factory()->superadmin()->create())
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/index')
            ->where('users', fn ($users) => collect($users)->contains(
                fn ($row) => $row['email'] === $user->email
                    && $row['username'] === $user->name
                    && in_array(['outlet' => 'Cabang A', 'roles' => ['cashier']], $row['outlets'], true)
            )));
});

test('non superadmin users cannot list admin users', function () {
    $this->actingAs(User::factory()->create()->assignRole(testRole('admin')))
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('superadmin can create a user in an outlet with roles and a verification email', function () {
    Notification::fake();
    $outlet = Team::factory()->create(['name' => 'Cabang B']);
    app(PermissionRegistrar::class)->setPermissionsTeamId($outlet->id);
    testRole('cashier');

    $this->actingAs(User::factory()->superadmin()->create())
        ->post(route('admin.users.store'), [
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'outlet' => $outlet->slug,
            'roles' => ['cashier'],
            'verified' => '0',
        ])
        ->assertRedirect(route('admin.users.index'));

    $user = User::where('email', 'budi@example.com')->firstOrFail();

    expect($user->email_verified_at)->toBeNull()
        ->and($user->teams()->pluck('teams.id')->all())->toBe([$outlet->id]);
    Notification::assertSentTo($user, VerifyEmail::class);
});

test('superadmin can edit a user without changing the password', function () {
    $user = User::factory()->create();
    $hash = $user->password;

    $this->actingAs(User::factory()->superadmin()->create())
        ->put(route('admin.users.update', $user), ['name' => 'Baru', 'email' => 'baru@example.com'])
        ->assertRedirect(route('admin.users.index'));

    expect($user->fresh()->name)->toBe('Baru')->and($user->fresh()->password)->toBe($hash);
});

test('superadmin can activate and deactivate a user', function () {
    $user = User::factory()->unverified()->create();
    $admin = User::factory()->superadmin()->create();

    $this->actingAs($admin)->post(route('admin.users.activate', $user))->assertRedirect();
    expect($user->fresh()->email_verified_at)->not->toBeNull();

    $this->actingAs($admin)->post(route('admin.users.deactivate', $user))->assertRedirect();
    expect($user->fresh()->email_verified_at)->toBeNull();

    $this->actingAs($admin)->post(route('admin.users.deactivate', $admin))->assertForbidden();
});

test('superadmin can send a verification email only to unverified users', function () {
    Notification::fake();
    $unverified = User::factory()->unverified()->create();
    $verified = User::factory()->create();
    $admin = User::factory()->superadmin()->create();

    $this->actingAs($admin)->post(route('admin.users.verification-notification', $unverified))->assertRedirect();
    $this->actingAs($admin)->post(route('admin.users.verification-notification', $verified))->assertRedirect();

    Notification::assertSentTo($unverified, VerifyEmail::class);
    Notification::assertNotSentTo($verified, VerifyEmail::class);
});

test('non superadmin users cannot manage admin users', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAs(User::factory()->create()->assignRole(testRole('admin')));

    $this->get(route('admin.users.create'))->assertForbidden();
    $this->post(route('admin.users.activate', $user))->assertForbidden();
});
