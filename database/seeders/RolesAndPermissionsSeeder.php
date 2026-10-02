<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Permission catalog grouped by sidebar menu group, then by menu. The order mirrors the
     * sidebar and is the order shown in the role form. Roles are not seeded; they are created
     * per outlet from the Roles page.
     *
     * @var array<string, array<string, array<int, string>>>
     */
    public const PERMISSION_GROUPS = [
        'POS' => [
            'sales' => ['sales.view', 'sales.create', 'sales.refund'],
            'products' => ['products.view', 'products.create', 'products.update', 'products.delete'],
            'customers' => ['customers.view', 'customers.create', 'customers.update', 'customers.delete'],
            'suppliers' => ['suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete'],
            'inventory' => ['inventory.view', 'inventory.adjust'],
            'reports' => ['reports.view'],
        ],
        'Master Data' => [
            'categories' => ['categories.view', 'categories.create', 'categories.update', 'categories.delete'],
            'units' => ['units.view', 'units.create', 'units.update', 'units.delete'],
            'prices' => ['prices.view', 'prices.create', 'prices.update', 'prices.delete'],
            'taxes' => ['taxes.view', 'taxes.create', 'taxes.update', 'taxes.delete'],
        ],
        'Administration' => [
            'users' => ['users.view', 'users.create', 'users.update', 'users.delete'],
            'roles' => ['roles.view', 'roles.create', 'roles.update', 'roles.delete'],
        ],
    ];

    /**
     * Seed the permission catalog.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSION_GROUPS as $menus) {
            foreach ($menus as $permissions) {
                foreach ($permissions as $permission) {
                    Permission::findOrCreate($permission);
                }
            }
        }
    }
}
