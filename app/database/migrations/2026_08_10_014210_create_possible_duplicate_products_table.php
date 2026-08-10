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
        Schema::create('possible_duplicate_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_a_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_b_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('similarity_score', 4, 3);
            $table->enum('status', ['pending', 'merged', 'dismissed'])->default('pending');
            $table->timestamps();

            $table->unique(['product_a_id', 'product_b_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('possible_duplicate_products');
    }
};
