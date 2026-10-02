<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payment methods become per-outlet master data. Sales keep a snapshot of the method name
     * in "payment_method" and point at the master record through "payment_method_id".
     */
    public function up(): void
    {
        $defaults = ['cash' => 'Cash', 'transfer' => 'Bank transfer', 'qris' => 'QRIS'];
        $cash = 'Cash';

        DB::table('teams')->pluck('id')->each(function (int $teamId) use ($defaults, $cash) {
            foreach ($defaults as $name) {
                DB::table('payment_methods')->insert([
                    'team_id' => $teamId, 'name' => $name, 'is_cash' => $name === $cash, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('payment_method_id')->nullable()->after('invoice_number')->constrained('payment_methods')->nullOnDelete();
            $table->string('payment_method', 100)->change();
        });

        foreach ($defaults as $code => $name) {
            DB::table('sales')->where('payment_method', $code)->update([
                'payment_method' => $name,
                'payment_method_id' => DB::raw("(select id from payment_methods where payment_methods.team_id = sales.team_id and payment_methods.name = '{$name}')"),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_method_id');
        });
    }
};
