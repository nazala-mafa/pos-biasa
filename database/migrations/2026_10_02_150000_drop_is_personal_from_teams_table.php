<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $personalIds = DB::table('teams')->where('is_personal', true)->pluck('id');

        if ($personalIds->isNotEmpty()) {
            $defaultId = DB::table('teams')->where('is_personal', false)->orderBy('id')->value('id');

            DB::table('users')->whereIn('current_team_id', $personalIds)->update(['current_team_id' => $defaultId]);
            DB::table('team_members')->whereIn('team_id', $personalIds)->delete();
            DB::table('teams')->whereIn('id', $personalIds)->delete();
        }

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('is_personal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->boolean('is_personal')->default(false)->after('slug');
        });
    }
};
