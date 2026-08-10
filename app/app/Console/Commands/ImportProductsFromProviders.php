<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ImportsFromProviders;
use App\Services\ProductMatchingService;
use App\StoreProviders\MockCatalogProvider;
use App\StoreProviders\StoreProviderInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Ingests raw listings from mock store providers and runs each one through
 * ProductMatchingService, instead of relying on ProductCatalogSeeder's
 * hand-grouped canonical_title pairing - see
 * docs/ENRICHMENT_ROADMAP.md Phase A/B.
 *
 * Intended to run against a fresh/empty catalogue (`php artisan
 * migrate:fresh && php artisan providers:import`) as a separate demo path
 * from `db:seed`, which stays untouched so its product IDs remain stable
 * for the Phase 6 evaluation harness. For REAL data instead of mock, see
 * `providers:import-live`.
 */
#[Signature('providers:import')]
#[Description('Import listings from mock store providers, matching them into canonical products.')]
class ImportProductsFromProviders extends Command
{
    use ImportsFromProviders;

    public function handle(ProductMatchingService $matcher): int
    {
        $this->importFromProviders($this->providers(), $matcher);

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
