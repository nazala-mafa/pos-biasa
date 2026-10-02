<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Permissions grouped by the roles that receive them.
     * The admin role always receives every permission; its entry lists admin-only ones.
     *
     * @var array<string, array<int, string>>
     */
    private const ROLE_PERMISSIONS = [
        'admin' => [
            'users.view', 'users.create', 'users.update', 'users.delete',
            'roles.view', 'roles.create', 'roles.update', 'roles.delete',
            'categories.create', 'categories.update', 'categories.delete',
            'units.create', 'units.update', 'units.delete',
            'prices.create', 'prices.update', 'prices.delete',
            'taxes.create', 'taxes.update', 'taxes.delete',
            'customers.delete', 'suppliers.delete',
        ],
        'manager' => [
            'products.view', 'products.create', 'products.update', 'products.delete',
            'customers.view', 'customers.create', 'customers.update',
            'suppliers.view', 'suppliers.create', 'suppliers.update',
            'inventory.view', 'inventory.adjust',
            'sales.view', 'sales.create', 'sales.refund',
            'reports.view',
            'categories.view', 'units.view', 'prices.view', 'taxes.view',
        ],
        'cashier' => [
            'products.view',
            'customers.view', 'customers.create',
            'sales.view', 'sales.create',
        ],
    ];

    /**
     * Seed the application's roles and permissions.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        $allPermissions = collect(self::ROLE_PERMISSIONS)->flatten()->unique();

        foreach ($allPermissions as $permission) {
            Permission::findOrCreate($permission);
        }

        Role::findOrCreate('admin')->syncPermissions($allPermissions->all());

        foreach (collect(self::ROLE_PERMISSIONS)->except('admin') as $role => $permissions) {
            Role::findOrCreate($role)->syncPermissions($permissions);
        }
    }
}
