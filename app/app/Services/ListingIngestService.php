<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Store;
use App\StoreProviders\StoreProviderInterface;

/**
 * The match-and-upsert core shared by every provider import path: the
 * batch Artisan commands (providers:import, providers:import-live) and,
 * now, LiveSearchFallbackService's on-demand single-query path. Pulled out
 * of the Console-only ImportsFromProviders trait so a web request can call
 * it too, without depending on Artisan's Command base class.
 */
class ListingIngestService
{
    public function __construct(private readonly ProductMatchingService $matcher)
    {
    }

    public function findOrCreateStore(StoreProviderInterface $provider): Store
    {
        return Store::updateOrCreate(
            ['slug' => $provider->slug()],
            ['name' => $provider->name(), 'base_url' => $provider->baseUrl(), 'is_active' => true, 'origin' => $provider->origin()],
        );
    }

    /**
     * Matches each listing onto an existing or new product and upserts its
     * store_id/product_id price row.
     *
     * @param  array<int, array>  $listings
     * @return array{listings: int, new_products: int}
     */
    public function ingest(Store $store, array $listings): array
    {
        $totalListings = 0;
        $totalNewProducts = 0;

        foreach ($listings as $listing) {
            $candidates = Product::where('category', $listing['category'] ?? null)->get();
            $productCountBefore = Product::count();

            $product = $this->matcher->matchOrCreate($listing, $candidates);

            if (Product::count() > $productCountBefore) {
                $totalNewProducts++;
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
                    'image_url' => $listing['image_url'] ?? null,
                    'last_checked_at' => now(),
                ],
            );

            $totalListings++;
        }

        return ['listings' => $totalListings, 'new_products' => $totalNewProducts];
    }
}
