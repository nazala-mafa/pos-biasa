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
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('sku');
            $table->string('name');
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
