<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Find or create a role of the current permissions team with a preset of permissions.
 * The app seeds no roles, so tests build the ones they need.
 */
function testRole(string $name): Role
{
    $presets = [
        'admin' => [
            'products.*', 'customers.*', 'suppliers.*', 'inventory.*', 'sales.*', 'reports.*',
            'categories.*', 'units.*', 'prices.*', 'taxes.*', 'users.*', 'roles.*',
            'returns.*', 'shifts.*', 'purchases.*', 'stock-opnames.*', 'payment-methods.*',
        ],
        'manager' => [
            'products.*', 'customers.view', 'customers.create', 'customers.update',
            'suppliers.view', 'suppliers.create', 'suppliers.update', 'inventory.*', 'sales.*', 'reports.*',
            'categories.view', 'units.view', 'prices.view', 'taxes.view',
            'returns.*', 'shifts.*', 'purchases.*', 'stock-opnames.*', 'payment-methods.view',
        ],
        'cashier' => ['products.view', 'customers.view', 'customers.create', 'sales.view', 'sales.create', 'shifts.view', 'shifts.open', 'shifts.close'],
    ];

    $all = Permission::pluck('name');
    $permissions = $all->filter(fn (string $permission) => collect($presets[$name])->contains(
        fn (string $pattern) => fnmatch($pattern, $permission),
    ))->all();

    $role = Role::firstOrCreate([
        'name' => $name,
        'guard_name' => config('auth.defaults.guard'),
        'team_id' => app(PermissionRegistrar::class)->getPermissionsTeamId(),
    ]);

    if ($role->wasRecentlyCreated) {
        $role->syncPermissions($permissions);
    }

    return $role;
}
