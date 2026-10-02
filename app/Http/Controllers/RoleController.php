<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function index(Request $request): Response
    {
        return Inertia::render('roles/index', [
            'roles' => $this->visibleRoles($request->user()->currentTeam)
                ->orderBy('name')
                ->get()
                ->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'usersCount' => $role->users_count,
                    'permissionsCount' => $role->permissions_count,
                    'isProtected' => $this->isProtected($role),
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
        $role = Role::create(['name' => $request->validated('name'), 'team_id' => $request->user()->current_team_id]);
        $role->syncPermissions($request->validated('permissions', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role created.')]);

        return to_route('roles.index');
    }

    /**
     * Show the form for editing a role.
     */
    public function edit(Request $request, string $currentTeam, Role $role): Response
    {
        $this->ensureVisible($request, $role);

        return Inertia::render('roles/form', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
                'isProtected' => $this->isProtected($role),
            ],
            'permissions' => $this->permissionNames(),
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(UpdateRoleRequest $request, string $currentTeam, Role $role): RedirectResponse
    {
        $this->ensureVisible($request, $role);
        abort_if($this->isProtected($role), 403, __('This role cannot be modified.'));

        $role->update(['name' => $request->validated('name')]);
        $role->syncPermissions($request->validated('permissions', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role updated.')]);

        return to_route('roles.index');
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Request $request, string $currentTeam, Role $role): RedirectResponse
    {
        $this->ensureVisible($request, $role);
        abort_if($this->isProtected($role), 403, __('This role cannot be deleted.'));

        $role->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role deleted.')]);

        return to_route('roles.index');
    }

    /**
     * Roles of the given outlet plus the shared default roles (no outlet).
     *
     * @return Builder<Role>
     */
    private function visibleRoles(Team $outlet): Builder
    {
        return Role::query()
            ->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $outlet->id))
            ->withCount(['users', 'permissions']);
    }

    private function ensureVisible(Request $request, Role $role): void
    {
        abort_unless($this->visibleRoles($request->user()->currentTeam)->whereKey($role->id)->exists(), 404);
    }

    /**
     * The admin role and shared default roles are managed outside an outlet.
     */
    private function isProtected(Role $role): bool
    {
        return $role->name === self::PROTECTED_ROLE || $role->team_id === null;
    }

    /**
     * @return array<int, string>
     */
    private function permissionNames(): array
    {
        return Permission::query()->orderBy('name')->pluck('name')->all();
    }
}
