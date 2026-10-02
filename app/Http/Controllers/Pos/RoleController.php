<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;

use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Repositories\RoleRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    private const PROTECTED_ROLE = 'admin';

    public function __construct(private RoleRepository $roles) {}

    /**
     * Display a listing of roles.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('roles.view');

        return Inertia::render('roles/index', [
            'roles' => $this->roles->visibleTo($request->user()->currentTeam)
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
        Gate::authorize('roles.create');

        return Inertia::render('roles/form', [
            'role' => null,
            'permissions' => $this->roles->permissionNames(),
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->roles->create($request->user()->currentTeam, $request->validated('name'), $request->validated('permissions', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role created.')]);

        return to_route('roles.index');
    }

    /**
     * Show the form for editing a role.
     */
    public function edit(Request $request, string $currentTeam, Role $role): Response
    {
        Gate::authorize('roles.update');

        $this->ensureVisible($request, $role);

        return Inertia::render('roles/form', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
                'isProtected' => $this->isProtected($role),
            ],
            'permissions' => $this->roles->permissionNames(),
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(UpdateRoleRequest $request, string $currentTeam, Role $role): RedirectResponse
    {
        $this->ensureVisible($request, $role);
        abort_if($this->isProtected($role), 403, __('This role cannot be modified.'));

        $this->roles->update($role, $request->validated('name'), $request->validated('permissions', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role updated.')]);

        return to_route('roles.index');
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Request $request, string $currentTeam, Role $role): RedirectResponse
    {
        Gate::authorize('roles.delete');

        $this->ensureVisible($request, $role);
        abort_if($this->isProtected($role), 403, __('This role cannot be deleted.'));

        $this->roles->delete($role);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role deleted.')]);

        return to_route('roles.index');
    }

    private function ensureVisible(Request $request, Role $role): void
    {
        abort_unless($this->roles->isVisibleTo($request->user()->currentTeam, $role), 404);
    }

    /**
     * The admin role and shared default roles are managed outside an outlet.
     */
    private function isProtected(Role $role): bool
    {
        return $role->name === self::PROTECTED_ROLE || $role->team_id === null;
    }
}
