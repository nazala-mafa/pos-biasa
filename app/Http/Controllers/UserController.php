<?php

namespace App\Http\Controllers;

use App\Enums\TeamRole;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     *
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:users.view', only: ['index']),
            new Middleware('permission:users.create', only: ['create', 'store']),
            new Middleware('permission:users.update', only: ['edit', 'update']),
            new Middleware('permission:users.delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('users/index', [
            'users' => $this->outletUsers($request->user()->currentTeam)
                ->where('is_superadmin', false)
                ->with('roles:id,name')
                ->orderBy('name')
                ->get()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name'),
                    'createdAt' => $user->created_at?->toDateString(),
                ]),
        ]);
    }

    /**
     * Show the form for creating a user.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('users/form', [
            'user' => null,
            'roles' => $this->roleNames($request->user()->currentTeam),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $outlet = $request->user()->currentTeam;

        DB::transaction(function () use ($request, $outlet) {
            $user = User::create($request->safe()->only(['name', 'email', 'password']));
            $user->forceFill(['email_verified_at' => now()])->save();

            $outlet->memberships()->create(['user_id' => $user->id, 'role' => TeamRole::Member]);
            $user->switchTeam($outlet);

            $user->syncRoles($request->validated('roles', []));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return to_route('users.index');
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(Request $request, string $currentTeam, User $user): Response
    {
        $this->ensureBelongsToOutlet($request, $user);

        return Inertia::render('users/form', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
            ],
            'roles' => $this->roleNames($request->user()->currentTeam),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, string $currentTeam, User $user): RedirectResponse
    {
        $this->ensureBelongsToOutlet($request, $user);

        $user->fill($request->safe()->only(['name', 'email']));

        if ($request->filled('password')) {
            $user->password = $request->validated('password');
        }

        $user->save();
        $user->syncRoles($request->validated('roles', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('users.index');
    }

    /**
     * Remove the user from the current outlet, deleting the account when no other (non-personal) outlet remains.
     */
    public function destroy(Request $request, string $currentTeam, User $user): RedirectResponse
    {
        $this->ensureBelongsToOutlet($request, $user);

        abort_if($user->is($request->user()), 403, __('You cannot delete your own account here.'));

        $outlet = $request->user()->currentTeam;

        DB::transaction(function () use ($user, $outlet) {
            $user->roles()->detach();
            $outlet->memberships()->where('user_id', $user->id)->delete();

            if ($user->current_team_id === $outlet->id) {
                $user->update(['current_team_id' => $user->fallbackTeam()?->id]);
            }

            if (! $user->teams()->where('is_personal', false)->exists()) {
                $user->delete();
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deleted.')]);

        return to_route('users.index');
    }

    /**
     * Users that are members of the given outlet.
     *
     * @return Builder<User>
     */
    private function outletUsers(Team $outlet): Builder
    {
        return User::query()->whereHas('teams', fn ($query) => $query->where('teams.id', $outlet->id));
    }

    private function ensureBelongsToOutlet(Request $request, User $user): void
    {
        abort_unless($this->outletUsers($request->user()->currentTeam)->whereKey($user->id)->exists(), 404);
    }

    /**
     * Role names assignable within the given outlet: its own roles and the shared defaults.
     *
     * @return array<int, string>
     */
    private function roleNames(Team $outlet): array
    {
        return Role::query()
            ->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $outlet->id))
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }
}
