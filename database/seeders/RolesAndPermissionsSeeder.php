<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Repositories\RoleRepository;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seed the default roles and permissions of every outlet.
     */
    public function run(RoleRepository $roles): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Team::query()->each(fn (Team $outlet) => $roles->createDefaultsFor($outlet));
    }
}
