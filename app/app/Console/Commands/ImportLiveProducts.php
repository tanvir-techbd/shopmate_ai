<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ImportsFromProviders;
use App\Services\ListingIngestService;
use App\StoreProviders\OthobaLiveProvider;
use App\StoreProviders\StoreProviderInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Real live data, not mock - fetches current listings from real store
 * websites over HTTP (server-rendered pages only, no anti-bot bypass) and
 * runs them through the same matching pipeline as `providers:import`. See
 * docs/ENRICHMENT_ROADMAP.md Phase C-lite for what this covers and why
 * (e.g. Daraz specifically) it deliberately doesn't.
 *
 * Meant to run periodically in the background (see routes/console.php) for
 * the fixed SEED_QUERIES categories - not on every user search, which
 * keeps the common case instant and avoids hammering the source site on
 * unpredictable user traffic. A query outside those categories (e.g.
 * "noodles" before it was added) is handled by a separate, narrower path
 * instead: LiveSearchFallbackService, which only ever fires when local
 * search already came back empty, and only does one single-query lookup
 * for the exact thing that was typed rather than this command's whole
 * SEED_QUERIES batch.
 */
#[Signature('providers:import-live')]
#[Description('Fetch real listings from live store websites and match them into canonical products.')]
class ImportLiveProducts extends Command
{
    use ImportsFromProviders;

    public function handle(ListingIngestService $ingest): int
    {
        $this->importFromProviders($this->providers(), $ingest);

        return self::SUCCESS;
    }

    /**
     * @return StoreProviderInterface[]
     */
    private function providers(): array
    {
        return [
            new OthobaLiveProvider(),
        ];
    }
}
