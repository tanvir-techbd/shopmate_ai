<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lives on the listing, not the canonical product: a photo is a concrete,
 * per-store asset (each store photographs/sources its own), and the
 * matcher already lets one canonical product carry several independently
 * worded listings - there's no single "the" photo to hang off it. The
 * mock catalogue (ProductCatalogSeeder) has no real photos to attach
 * honestly and leaves this null; OthobaLiveProvider populates it from a
 * real field in the same public search response already used for titles
 * and prices.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->string('image_url')->nullable()->after('product_url');
        });
    }

    public function down(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->dropColumn('image_url');
        });
    }
};
