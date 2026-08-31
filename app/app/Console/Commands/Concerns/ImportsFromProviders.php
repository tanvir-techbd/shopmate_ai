<?php

namespace App\Console\Commands\Concerns;

use App\Models\Product;
use App\Services\ListingIngestService;
use App\StoreProviders\StoreProviderInterface;

/**
 * Shared by ImportProductsFromProviders (mock) and ImportLiveProducts
 * (real scraping) - both just fetch a different set of providers and run
 * them through ListingIngestService's match-and-upsert pipeline (also used
 * by LiveSearchFallbackService's on-demand single-query path). See
 * docs/ENRICHMENT_ROADMAP.md Phase A/B and Phase C-lite.
 */
trait ImportsFromProviders
{
    /**
     * @param  StoreProviderInterface[]  $providers
     */
    protected function importFromProviders(array $providers, ListingIngestService $ingest): void
    {
        $totalListings = 0;
        $totalNewProducts = 0;

        foreach ($providers as $provider) {
            $store = $ingest->findOrCreateStore($provider);

            $listings = $provider->fetchListings();
            $this->info("{$provider->name()}: ".count($listings).' listings');

            $result = $ingest->ingest($store, $listings);
            $totalListings += $result['listings'];
            $totalNewProducts += $result['new_products'];
        }

        $this->newLine();
        $this->info("Imported {$totalListings} listings into {$totalNewProducts} new products (plus matches onto existing ones).");
        $this->info('Products in catalogue: '.Product::count());

        $this->call('products:find-duplicates');
    }
}
