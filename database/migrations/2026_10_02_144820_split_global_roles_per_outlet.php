<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Give every outlet its own copy of the shared roles (no outlet), move existing
     * role assignments to those copies, then remove the shared roles.
     */
    public function up(): void
    {
        $tables = config('permission.table_names');
        $teamKey = config('permission.column_names.team_foreign_key');

        $sharedRoles = DB::table($tables['roles'])->whereNull($teamKey)->get();
        $teamIds = DB::table('teams')->pluck('id');

        DB::transaction(function () use ($sharedRoles, $teamIds, $tables, $teamKey) {
            foreach ($sharedRoles as $shared) {
                $permissionIds = DB::table($tables['role_has_permissions'])->where('role_id', $shared->id)->pluck('permission_id');

                foreach ($teamIds as $teamId) {
                    $copyId = DB::table($tables['roles'])->insertGetId([
                        'name' => $shared->name,
                        'guard_name' => $shared->guard_name,
                        $teamKey => $teamId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table($tables['role_has_permissions'])->insert(
                        $permissionIds->map(fn ($permissionId) => ['permission_id' => $permissionId, 'role_id' => $copyId])->all()
                    );

                    DB::table($tables['model_has_roles'])
                        ->where('role_id', $shared->id)
                        ->where($teamKey, $teamId)
                        ->update(['role_id' => $copyId]);
                }

                DB::table($tables['role_has_permissions'])->where('role_id', $shared->id)->delete();
                DB::table($tables['model_has_roles'])->where('role_id', $shared->id)->delete();
                DB::table($tables['roles'])->where('id', $shared->id)->delete();
            }
        });

        app('cache')->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
