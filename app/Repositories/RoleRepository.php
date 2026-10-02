<?php

namespace App\Repositories;

use App\Models\Team;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleRepository
{
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
     * Permissions grouped by sidebar group and menu, in sidebar order.
     *
     * @return array<int, array{group: string, menus: array<int, array{menu: string, permissions: array<int, string>}>}>
     */
    public function permissionGroups(): array
    {
        $existing = Permission::query()->pluck('name')->all();

        return collect(RolesAndPermissionsSeeder::PERMISSION_GROUPS)
            ->map(fn (array $menus, string $group) => [
                'group' => $group,
                'menus' => collect($menus)
                    ->map(fn (array $permissions, string $menu) => [
                        'menu' => $menu,
                        'permissions' => array_values(array_intersect($permissions, $existing)),
                    ])
                    ->filter(fn (array $menu) => $menu['permissions'] !== [])
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $group) => $group['menus'] !== [])
            ->values()
            ->all();
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
