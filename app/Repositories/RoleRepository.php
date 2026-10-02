<?php

namespace App\Repositories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleRepository
{
    /**
     * Permissions granted by default to each role of a new outlet. The roles are ordinary and
     * editable per outlet; full access beyond them belongs to superadmin users only.
     *
     * @var array<string, array<int, string>>
     */
    public const DEFAULT_ROLE_PERMISSIONS = [
        'admin' => [
            'products.view', 'products.create', 'products.update', 'products.delete',
            'customers.view', 'customers.create', 'customers.update',
            'suppliers.view', 'suppliers.create', 'suppliers.update',
            'inventory.view', 'inventory.adjust',
            'sales.view', 'sales.create', 'sales.refund',
            'reports.view',
            'categories.view', 'units.view', 'prices.view', 'taxes.view',
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
     * Create the default admin, manager and cashier roles of the outlet. Existing roles are left untouched.
     */
    public function createDefaultsFor(Team $outlet): void
    {
        foreach (collect(self::DEFAULT_ROLE_PERMISSIONS)->flatten()->unique() as $permission) {
            Permission::findOrCreate($permission);
        }

        foreach (self::DEFAULT_ROLE_PERMISSIONS as $name => $permissions) {
            $role = Role::firstOrCreate([
                'name' => $name,
                'guard_name' => config('auth.defaults.guard'),
                'team_id' => $outlet->id,
            ]);

            if ($role->wasRecentlyCreated) {
                $role->syncPermissions($permissions);
            }
        }
    }

    /**
     * Roles of the outlet with user and permission counts.
     *
     * @return Collection<int, Role>
     */
    public function visibleTo(Team $outlet): Collection
    {
        return $this->visibleQuery($outlet)
            ->withCount(['users', 'permissions'])
            ->orderBy('name')
            ->get();
    }

    public function isVisibleTo(Team $outlet, Role $role): bool
    {
        return $this->visibleQuery($outlet)->whereKey($role->id)->exists();
    }

    /**
     * Names of the roles assignable within the outlet.
     *
     * @return array<int, string>
     */
    public function namesFor(Team $outlet): array
    {
        return $this->visibleQuery($outlet)->orderBy('name')->pluck('name')->all();
    }

    /**
     * @return array<int, string>
     */
    public function permissionNames(): array
    {
        return Permission::query()->orderBy('name')->pluck('name')->all();
    }

    public function count(): int
    {
        return Role::count();
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function create(Team $outlet, string $name, array $permissions): Role
    {
        $role = Role::create(['name' => $name, 'team_id' => $outlet->id]);
        $role->syncPermissions($permissions);

        return $role;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function update(Role $role, string $name, array $permissions): Role
    {
        $role->update(['name' => $name]);
        $role->syncPermissions($permissions);

        return $role;
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }

    /**
     * @return Builder<Role>
     */
    private function visibleQuery(Team $outlet): Builder
    {
        return Role::query()
            ->where('team_id', $outlet->id);
    }
}
