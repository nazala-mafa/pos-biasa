<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\User;
use App\Repositories\UserRepository;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private UserRepository $users) {}

    public function index(): Response
    {
        $assignedRoles = $this->users->assignedRolesByOutlet();

        $users = $this->users->allWithOutlets()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'username' => $user->name,
                'email' => $user->email,
                'createdAt' => $user->created_at?->toDateString(),
                'outlets' => $user->teamMemberships->map(fn (Membership $membership) => [
                    'outlet' => $membership->team->name,
                    'roles' => ($assignedRoles[$user->id.':'.$membership->team_id] ?? collect())->pluck('name')->values(),
                ])->values(),
            ]);

        return Inertia::render('admin/users/index', ['users' => $users]);
    }
}
