<?php

namespace App\Console\Commands\Concerns;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Store;
use App\Services\ProductMatchingService;
use App\StoreProviders\StoreProviderInterface;

/**
 * Shared by ImportProductsFromProviders (mock) and ImportLiveProducts
 * (real scraping) - both just fetch a different set of providers and run
 * them through the same match-and-upsert pipeline. See
 * docs/ENRICHMENT_ROADMAP.md Phase A/B and Phase C-lite.
 */
trait ImportsFromProviders
{
    /**
     * @param  StoreProviderInterface[]  $providers
     */
    protected function importFromProviders(array $providers, ProductMatchingService $matcher): void
    {
        $totalListings = 0;
        $totalNewProducts = 0;

        foreach ($providers as $provider) {
            $store = Store::updateOrCreate(
                ['slug' => $provider->slug()],
                ['name' => $provider->name(), 'base_url' => $provider->baseUrl(), 'is_active' => true],
            );

            $listings = $provider->fetchListings();
            $this->info("{$provider->name()}: ".count($listings).' listings');

            foreach ($listings as $listing) {
                $candidates = Product::where('category', $listing['category'] ?? null)->get();
                $productCountBefore = Product::count();

                $product = $matcher->matchOrCreate($listing, $candidates);

                if (Product::count() > $productCountBefore) {
                    $totalNewProducts++;
                } else {
                    $this->line("  matched \"{$listing['title']}\" -> #{$product->id} {$product->canonical_title}");
                }

                ProductPrice::updateOrCreate(
                    ['product_id' => $product->id, 'store_id' => $store->id],
                    [
                        'store_title' => $listing['title'],
                        'price' => $listing['price'],
                        'delivery_charge' => $listing['delivery_charge'],
                        'rating' => $listing['rating'],
                        'review_count' => $listing['review_count'],
                        'in_stock' => $listing['in_stock'],
                        'product_url' => $listing['product_url'] ?? '#',
                        'last_checked_at' => now(),
                    ],
                );

                $totalListings++;
            }
        }

        $this->newLine();
        $this->info("Imported {$totalListings} listings into {$totalNewProducts} new products (plus matches onto existing ones).");
        $this->info('Products in catalogue: '.Product::count());

        $this->call('products:find-duplicates');
    }
}
