<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller implements HasMiddleware
{
    private const PROTECTED_ROLE = 'admin';

    /**
     * Get the middleware that should be assigned to the controller.
     *
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:roles.view', only: ['index']),
            new Middleware('permission:roles.create', only: ['create', 'store']),
            new Middleware('permission:roles.update', only: ['edit', 'update']),
            new Middleware('permission:roles.delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of roles.
     */
    public function index(): Response
    {
        return Inertia::render('roles/index', [
            'roles' => Role::query()
                ->withCount(['users', 'permissions'])
                ->orderBy('name')
                ->get()
                ->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'usersCount' => $role->users_count,
                    'permissionsCount' => $role->permissions_count,
                    'isProtected' => $role->name === self::PROTECTED_ROLE,
                    'createdAt' => $role->created_at?->toDateString(),
                ]),
        ]);
    }

    /**
     * Show the form for creating a role.
     */
    public function create(): Response
    {
        return Inertia::render('roles/form', [
            'role' => null,
            'permissions' => $this->permissionNames(),
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create(['name' => $request->validated('name'), 'team_id' => null]);
        $role->syncPermissions($request->validated('permissions', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role created.')]);

        return to_route('roles.index');
    }

    /**
     * Show the form for editing a role.
     */
    public function edit(Role $role): Response
    {
        return Inertia::render('roles/form', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
                'isProtected' => $role->name === self::PROTECTED_ROLE,
            ],
            'permissions' => $this->permissionNames(),
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        abort_if($role->name === self::PROTECTED_ROLE, 403, __('The admin role cannot be modified.'));

        $role->update(['name' => $request->validated('name')]);
        $role->syncPermissions($request->validated('permissions', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role updated.')]);

        return to_route('roles.index');
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->name === self::PROTECTED_ROLE, 403, __('The admin role cannot be deleted.'));

        $role->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role deleted.')]);

        return to_route('roles.index');
    }

    /**
     * @return array<int, string>
     */
    private function permissionNames(): array
    {
        return Permission::query()->orderBy('name')->pluck('name')->all();
    }
}
