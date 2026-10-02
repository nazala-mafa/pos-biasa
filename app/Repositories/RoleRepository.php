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
     * Roles of the outlet plus the shared default roles (no outlet), with user and permission counts.
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
            ->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $outlet->id));
    }
}
