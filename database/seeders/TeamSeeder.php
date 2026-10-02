<?php

namespace Database\Seeders;

use App\Actions\Teams\AssignDefaultTeam;
use App\Enums\TeamRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Seed the default POS team with the superadmin as its admin.
     */
    public function run(AssignDefaultTeam $assignDefaultTeam): void
    {
        $users = [
            'superadmin@example.com' => TeamRole::Admin,
        ];

        foreach ($users as $email => $role) {
            $user = User::where('email', $email)->first();

            if ($user !== null) {
                $assignDefaultTeam->handle($user, $role);
            }
        }
    }
}
