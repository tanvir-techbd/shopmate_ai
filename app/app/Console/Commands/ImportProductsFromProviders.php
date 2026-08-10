<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Store;
use App\Services\ProductMatchingService;
use App\StoreProviders\MockCatalogProvider;
use App\StoreProviders\StoreProviderInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Ingests raw listings from every registered StoreProviderInterface and
 * runs each one through ProductMatchingService, instead of relying on
 * ProductCatalogSeeder's hand-grouped canonical_title pairing - see
 * docs/ENRICHMENT_ROADMAP.md Phase A/B.
 *
 * Intended to run against a fresh/empty catalogue (`php artisan
 * migrate:fresh && php artisan providers:import`) as a separate demo path
 * from `db:seed`, which stays untouched so its product IDs remain stable
 * for the Phase 6 evaluation harness.
 */
#[Signature('providers:import')]
#[Description('Import listings from all registered store providers, matching them into canonical products.')]
class ImportProductsFromProviders extends Command
{
    public function handle(ProductMatchingService $matcher): int
    {
        $totalListings = 0;
        $totalNewProducts = 0;

        foreach ($this->providers() as $provider) {
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

        return self::SUCCESS;
    }

    /**
     * @return StoreProviderInterface[]
     */
    private function providers(): array
    {
        return [
            new MockCatalogProvider('trendymart-bd', 'TrendyMart BD', 'https://example-trendymart.test'),
            new MockCatalogProvider('quickbazaar', 'QuickBazaar', 'https://example-quickbazaar.test'),
            new MockCatalogProvider('clickbuy-bd', 'ClickBuy BD', 'https://example-clickbuy.test'),
        ];
    }
}
