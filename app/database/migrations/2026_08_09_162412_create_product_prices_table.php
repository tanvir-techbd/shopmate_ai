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
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('store_title'); // raw title as listed on that store
            $table->decimal('price', 10, 2);
            $table->decimal('delivery_charge', 10, 2)->default(0);
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('review_count')->default(0);
            $table->boolean('in_stock')->default(true);
            $table->string('product_url')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'store_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
