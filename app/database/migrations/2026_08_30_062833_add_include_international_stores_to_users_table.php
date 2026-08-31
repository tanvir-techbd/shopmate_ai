<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user, off-by-default toggle: whether search results may include
 * international (cross-border) stores alongside domestic ones. See
 * StoreProviderInterface::origin() and the ai-service filtering in
 * main.py's chat_query(). Off by default so existing users see no change
 * in behaviour until they opt in from their profile.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('include_international_stores')->default(false)->after('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('include_international_stores');
        });
    }
};
