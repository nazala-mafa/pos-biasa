<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->decimal('opening_cash', 15, 2);
            $table->decimal('expected_cash', 15, 2)->nullable();
            $table->decimal('closing_cash', 15, 2)->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'user_id', 'closed_at']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('cashier_shift_id')->nullable()->after('user_id')->constrained('cashier_shifts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', fn (Blueprint $table) => $table->dropConstrainedForeignId('cashier_shift_id'));
        Schema::dropIfExists('cashier_shifts');
    }
};
