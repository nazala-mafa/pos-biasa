<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $assignedRoles = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', (new User)->getMorphClass())
            ->orderBy('roles.name')
            ->get(['model_has_roles.model_id as user_id', 'model_has_roles.team_id', 'roles.name'])
            ->groupBy(fn ($row) => $row->user_id.':'.$row->team_id);

        $users = User::query()
            ->with(['teamMemberships' => fn ($query) => $query
                ->whereHas('team', fn ($team) => $team->where('is_personal', false))
                ->with('team:id,name')])
            ->orderBy('name')
            ->get()
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
