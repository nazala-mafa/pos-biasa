<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private UserRepository $users, private RoleRepository $roles) {}

    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('users.view');

        return Inertia::render('users/index', [
            'users' => $this->users->membersOf($request->user()->currentTeam)
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
        Gate::authorize('users.create');

        return Inertia::render('users/form', [
            'user' => null,
            'roles' => $this->roles->namesFor($request->user()->currentTeam),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->users->createForOutlet(
            $request->user()->currentTeam,
            ['name' => $request->validated('name'), 'email' => $request->validated('email'), 'password' => $request->validated('password')],
            $request->validated('roles', []),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return to_route('users.index');
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(Request $request, string $currentTeam, User $user): Response
    {
        Gate::authorize('users.update');

        $this->ensureBelongsToOutlet($request, $user);

        return Inertia::render('users/form', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
            ],
            'roles' => $this->roles->namesFor($request->user()->currentTeam),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, string $currentTeam, User $user): RedirectResponse
    {
        $this->ensureBelongsToOutlet($request, $user);

        $this->users->update(
            $user,
            ['name' => $request->validated('name'), 'email' => $request->validated('email')],
            $request->validated('password'),
            $request->validated('roles', []),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('users.index');
    }

    /**
     * Remove the user from the current outlet, deleting the account when no other outlet remains.
     */
    public function destroy(Request $request, string $currentTeam, User $user): RedirectResponse
    {
        Gate::authorize('users.delete');

        $this->ensureBelongsToOutlet($request, $user);

        abort_if($user->is($request->user()), 403, __('You cannot delete your own account here.'));

        $this->users->removeFromOutlet($request->user()->currentTeam, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deleted.')]);

        return to_route('users.index');
    }

    private function ensureBelongsToOutlet(Request $request, User $user): void
    {
        abort_unless($this->users->isMemberOf($request->user()->currentTeam, $user), 404);
    }
}
