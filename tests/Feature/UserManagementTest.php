<?php

use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create()->assignRole(testRole('admin'));
    $this->outlet = $this->admin->currentTeam;
});

function outletMember(Team $outlet, ?User $user = null): User
{
    $user ??= User::factory()->create();
    $outlet->members()->attach($user, ['role' => TeamRole::Member->value]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($outlet->id);
    URL::defaults(['current_team' => $outlet->slug]);

    return $user;
}

test('admin can list users', function () {
    $this->actingAs($this->admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('users/index')->has('users', 1));
});

test('superadmins are excluded from the users list', function () {
    outletMember($this->outlet, User::factory()->superadmin()->create());

    $this->actingAs($this->admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('users', 1));
});

test('only members of the current outlet are listed', function () {
    User::factory()->create();
    outletMember($this->outlet);

    $this->actingAs($this->admin)
        ->get(route('users.index'))
        ->assertInertia(fn (Assert $page) => $page->has('users', 2));
});

test('users of other outlets cannot be managed', function () {
    $stranger = User::factory()->create();
    URL::defaults(['current_team' => $this->outlet->slug]);

    $this->actingAs($this->admin)->get(route('users.edit', $stranger))->assertNotFound();
    $this->actingAs($this->admin)->put(route('users.update', $stranger), ['name' => 'X', 'email' => 'x@example.com'])->assertNotFound();
    $this->actingAs($this->admin)->delete(route('users.destroy', $stranger))->assertNotFound();
    $this->assertModelExists($stranger);
});

test('users without permission are forbidden', function () {
    $cashier = User::factory()->create()->assignRole(testRole('cashier'));

    $this->actingAs($cashier)->get(route('users.index'))->assertForbidden();
    $this->actingAs($cashier)->post(route('users.store'), [])->assertForbidden();
});

test('admin can create a user with roles', function () {
    testRole('cashier');

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

    app(PermissionRegistrar::class)->setPermissionsTeamId($user->current_team_id);

    expect($user->hasRole('cashier'))->toBeTrue()
        ->and($user->current_team_id)->toBe($this->outlet->id)
        ->and($this->outlet->members()->whereKey($user->id)->exists())->toBeTrue()
        ->and($user->ownedTeams()->count())->toBe(0);
});

test('user creation validates input', function () {
    $this->actingAs($this->admin)
        ->post(route('users.store'), ['email' => $this->admin->email, 'roles' => ['nope']])
        ->assertSessionHasErrors(['name', 'email', 'password', 'roles.0']);
});

test('admin can update a user and keep password when blank', function () {
    $user = outletMember($this->outlet)->assignRole(testRole('cashier'));
    testRole('manager');
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
    $user = outletMember($this->outlet);
    Membership::where('user_id', $user->id)->where('team_id', '!=', $this->outlet->id)->delete();

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

test('deleting a user keeps the account when they belong to another outlet', function () {
    $user = outletMember($this->outlet);
    Team::factory()->create()->members()->attach($user, ['role' => TeamRole::Member->value]);
    URL::defaults(['current_team' => $this->outlet->slug]);

    $this->actingAs($this->admin)->delete(route('users.destroy', $user))->assertRedirect(route('users.index'));

    $this->assertModelExists($user);
    expect($this->outlet->members()->whereKey($user->id)->exists())->toBeFalse()
        ->and($user->fresh()->current_team_id)->not->toBe($this->outlet->id);
});

test('user seeder creates a superadmin and is idempotent', function () {
    $this->seed(UserSeeder::class);
    $this->seed(UserSeeder::class);

    expect(User::where('is_superadmin', true)->count())->toBe(1);
});
