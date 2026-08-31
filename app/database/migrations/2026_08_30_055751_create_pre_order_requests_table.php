<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Captures demand for products the catalogue genuinely doesn't have, so a
 * "not available" search isn't a dead end - see ChatController::send() and
 * PreOrderController. Deliberately not linked to a product_id: there is no
 * product to link to, that's the whole point.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pre_order_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('query');
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_order_requests');
    }
};
