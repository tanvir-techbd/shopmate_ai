<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tags each store domestic or international so search can optionally
 * exclude cross-border stores per user preference - see
 * StoreProviderInterface::origin() and User::include_international_stores.
 * Defaults every existing store (the mock BD stores, Othoba) to domestic,
 * which is correct for all of them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->enum('origin', ['domestic', 'international'])->default('domestic')->after('base_url');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('origin');
        });
    }
};
