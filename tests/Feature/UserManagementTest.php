<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create()->assignRole('admin');
});

test('admin can list users', function () {
    $this->actingAs($this->admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('users/index')->has('users', 1));
});

test('users without permission are forbidden', function () {
    $cashier = User::factory()->create()->assignRole('cashier');

    $this->actingAs($cashier)->get(route('users.index'))->assertForbidden();
    $this->actingAs($cashier)->post(route('users.store'), [])->assertForbidden();
});

test('admin can create a user with roles', function () {
    $this->actingAs($this->admin)
        ->post(route('users.store'), [
            'name' => 'New Cashier',
            'email' => 'cashier@example.com',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
            'roles' => ['cashier'],
        ])
        ->assertRedirect(route('users.index'));

    $user = User::where('email', 'cashier@example.com')->firstOrFail();

    expect($user->hasRole('cashier'))->toBeTrue()
        ->and($user->currentTeam->name)->toBe('POS')
        ->and($user->ownedTeams()->count())->toBe(0);
});

test('user creation validates input', function () {
    $this->actingAs($this->admin)
        ->post(route('users.store'), ['email' => $this->admin->email, 'roles' => ['nope']])
        ->assertSessionHasErrors(['name', 'email', 'password', 'roles.0']);
});

test('admin can update a user and keep password when blank', function () {
    $user = User::factory()->create()->assignRole('cashier');
    $originalPassword = $user->password;

    $this->actingAs($this->admin)
        ->put(route('users.update', $user), [
            'name' => 'Renamed',
            'email' => $user->email,
            'password' => '',
            'roles' => ['manager'],
        ])
        ->assertRedirect(route('users.index'));

    $user->refresh();

    expect($user->name)->toBe('Renamed')
        ->and($user->password)->toBe($originalPassword)
        ->and($user->hasRole('manager'))->toBeTrue()
        ->and($user->hasRole('cashier'))->toBeFalse();
});

test('admin can delete another user but not themselves', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)->delete(route('users.destroy', $user))->assertRedirect(route('users.index'));
    $this->assertModelMissing($user);

    $this->actingAs($this->admin)->delete(route('users.destroy', $this->admin))->assertForbidden();
});

test('superadmin flag grants every permission without a role', function () {
    $superadmin = User::factory()->superadmin()->create();

    expect($superadmin->can('users.delete'))->toBeTrue();

    $this->actingAs($superadmin)->get(route('users.index'))->assertOk();
    $this->actingAs($superadmin)->get(route('roles.index'))->assertOk();
});

test('regular users do not get superadmin access', function () {
    expect(User::factory()->create()->fresh()->is_superadmin)->toBeFalse();
});

test('user seeder creates a superadmin and is idempotent', function () {
    $this->seed(UserSeeder::class);
    $this->seed(UserSeeder::class);

    expect(User::where('is_superadmin', true)->count())->toBe(1)
        ->and(User::where('email', 'cashier@example.com')->first()->hasRole('cashier'))->toBeTrue();
});
