<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Real scraped titles (e.g. SEO-keyword-stuffed listings on Othoba) can
 * comfortably exceed 255 chars - found this the hard way when
 * `providers:import-live` crashed on a genuine product title. Raw SQL
 * (not ->change()) to avoid a doctrine/dbal dependency for one column
 * width bump.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE products MODIFY canonical_title VARCHAR(500) NOT NULL');
        DB::statement('ALTER TABLE product_prices MODIFY store_title VARCHAR(500) NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE products MODIFY canonical_title VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE product_prices MODIFY store_title VARCHAR(255) NOT NULL');
    }
};
