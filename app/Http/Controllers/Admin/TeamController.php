<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(): Response
    {
        $teams = Team::query()
            ->withCount('members')
            ->latest()
            ->get()
            ->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'isPersonal' => $team->is_personal,
                'membersCount' => $team->members_count,
                'createdAt' => $team->created_at?->toDateString(),
            ]);

        return Inertia::render('admin/teams/index', ['teams' => $teams]);
    }
}
