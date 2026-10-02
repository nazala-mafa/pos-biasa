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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('invoice_number');
            $table->string('payment_method', 20);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->decimal('paid', 15, 2);
            $table->decimal('change', 15, 2)->default(0);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'invoice_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
