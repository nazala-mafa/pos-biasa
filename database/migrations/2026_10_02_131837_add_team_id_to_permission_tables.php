<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the team (outlet) foreign key to databases created before permission teams were enabled.
     * Existing role assignments are moved to the default outlet.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $teamKey = config('permission.column_names.team_foreign_key');
        $pivotRole = config('permission.column_names.role_pivot_key') ?? 'role_id';
        $pivotPermission = config('permission.column_names.permission_pivot_key') ?? 'permission_id';
        $modelKey = config('permission.column_names.model_morph_key');

        if (! Schema::hasColumn($tableNames['roles'], $teamKey)) {
            Schema::table($tableNames['roles'], function (Blueprint $table) use ($teamKey) {
                $table->unsignedBigInteger($teamKey)->nullable()->index('roles_team_foreign_key_index');
            });
            Schema::table($tableNames['roles'], function (Blueprint $table) use ($teamKey) {
                $table->dropUnique(['name', 'guard_name']);
                $table->unique([$teamKey, 'name', 'guard_name']);
            });
        }

        $defaultTeamId = DB::table('teams')->orderBy('id')->value('id') ?? 0;

        $this->addTeamToPivot($tableNames['model_has_roles'], $teamKey, $pivotRole, $modelKey, $defaultTeamId, 'model_has_roles');
        $this->addTeamToPivot($tableNames['model_has_permissions'], $teamKey, $pivotPermission, $modelKey, $defaultTeamId, 'model_has_permissions');

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

    private function addTeamToPivot(string $table, string $teamKey, string $pivotKey, string $modelKey, int $defaultTeamId, string $indexPrefix): void
    {
        if (Schema::hasColumn($table, $teamKey)) {
            return;
        }

        $rows = DB::table($table)->get()->map(fn ($row) => (array) $row + [$teamKey => $defaultTeamId])->all();

        Schema::drop($table);

        Schema::create($table, function (Blueprint $blueprint) use ($teamKey, $pivotKey, $modelKey, $indexPrefix, $table) {
            $blueprint->unsignedBigInteger($pivotKey);
            $blueprint->string('model_type');
            $blueprint->unsignedBigInteger($modelKey);
            $blueprint->index([$modelKey, 'model_type'], $indexPrefix.'_model_id_model_type_index');
            $blueprint->foreign($pivotKey)
                ->references('id')
                ->on($table === config('permission.table_names.model_has_roles') ? config('permission.table_names.roles') : config('permission.table_names.permissions'))
                ->onDelete('cascade');
            $blueprint->unsignedBigInteger($teamKey);
            $blueprint->index($teamKey, $indexPrefix.'_team_foreign_key_index');
            $blueprint->primary([$teamKey, $pivotKey, $modelKey, 'model_type'], $indexPrefix.'_primary');
        });

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }
};
