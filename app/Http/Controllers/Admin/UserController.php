<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreAdminUserRequest;
use App\Http\Requests\User\UpdateAdminUserRequest;
use App\Models\Membership;
use App\Models\Team;
use App\Models\User;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private UserRepository $users, private RoleRepository $roles) {}

    /**
     * Display a listing of users.
     */
    public function index(): Response
    {
        $assignedRoles = $this->users->assignedRolesByOutlet();

        $users = $this->users->allWithOutlets()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'username' => $user->name,
                'email' => $user->email,
                'emailVerifiedAt' => $user->email_verified_at?->toDateTimeString(),
                'createdAt' => $user->created_at?->toDateString(),
                'outlets' => $user->teamMemberships->map(fn (Membership $membership) => [
                    'outlet' => $membership->team->name,
                    'roles' => ($assignedRoles[$user->id.':'.$membership->team_id] ?? collect())->pluck('name')->values(),
                ])->values(),
            ]);

        return Inertia::render('admin/users/index', ['users' => $users]);
    }

    /**
     * Show the form for creating a user.
     */
    public function create(): Response
    {
        return Inertia::render('admin/users/form', [
            'user' => null,
            'outlets' => $this->outletOptions(),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        $outlet = Team::where('slug', $request->validated('outlet'))->firstOrFail();
        $verified = $request->boolean('verified');

        $user = $this->users->createForOutlet(
            $outlet,
            $request->safe()->only(['name', 'email', 'password']),
            $request->validated('roles', []),
            $verified,
        );

        if (! $verified) {
            $user->sendEmailVerificationNotification();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return to_route('admin.users.index');
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(User $user): Response
    {
        return Inertia::render('admin/users/form', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'emailVerifiedAt' => $user->email_verified_at?->toDateTimeString(),
            ],
            'outlets' => [],
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateAdminUserRequest $request, User $user): RedirectResponse
    {
        $this->users->updateAccount(
            $user,
            $request->safe()->only(['name', 'email']),
            $request->validated('password'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('admin.users.index');
    }

    /**
     * Activate the account by marking its email as verified.
     */
    public function activate(User $user): RedirectResponse
    {
        $this->users->activate($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User activated.')]);

        return back();
    }

    /**
     * Deactivate the account by clearing its email verification.
     */
    public function deactivate(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 403, __('You cannot deactivate your own account.'));

        $this->users->deactivate($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deactivated.')]);

        return back();
    }

    /**
     * Send the email verification link to the user.
     */
    public function sendVerification(User $user): RedirectResponse
    {
        if ($user->hasVerifiedEmail()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This email is already verified.')]);

            return back();
        }

        $user->sendEmailVerificationNotification();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Verification email sent.')]);

        return back();
    }

    /**
     * Outlets with the role names assignable in each.
     *
     * @return array<int, array{slug: string, name: string, roles: array<int, string>}>
     */
    private function outletOptions(): array
    {
        return Team::query()->orderBy('name')->get()
            ->map(fn (Team $outlet) => [
                'slug' => $outlet->slug,
                'name' => $outlet->name,
                'roles' => $this->roles->namesFor($outlet),
            ])
            ->all();
    }
}
