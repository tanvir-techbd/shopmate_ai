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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('canonical_title');
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->text('description')->nullable();
            $table->json('attributes')->nullable(); // colour, size, etc.
            $table->json('embedding')->nullable();  // sentence embedding, stored as JSON float array
            $table->timestamps();

            $table->index('category');
            $table->index('brand');
            $table->fullText(['canonical_title', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
