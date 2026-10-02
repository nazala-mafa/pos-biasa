<?php

namespace App\Actions\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AssignDefaultTeam
{
    public const NAME = 'POS';

    /**
     * Add the user to the shared default POS team (creating it if missing) and make it their current team.
     */
    public function handle(User $user, TeamRole $role = TeamRole::Member): Team
    {
        return DB::transaction(function () use ($user, $role) {
            $team = Team::where('name', self::NAME)->where('is_personal', false)->first()
                ?? Team::create(['name' => self::NAME, 'is_personal' => false]);

            $team->memberships()->firstOrCreate(['user_id' => $user->id], ['role' => $role]);

            $user->switchTeam($team);

            return $team;
        });
    }
}
