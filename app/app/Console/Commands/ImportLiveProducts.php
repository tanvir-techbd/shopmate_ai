<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ImportsFromProviders;
use App\Services\ProductMatchingService;
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
 * Meant to run periodically in the background (see routes/console.php),
 * not on every user search - keeps search itself instant and avoids
 * hammering the source site on unpredictable user traffic.
 */
#[Signature('providers:import-live')]
#[Description('Fetch real listings from live store websites and match them into canonical products.')]
class ImportLiveProducts extends Command
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
            new OthobaLiveProvider(),
        ];
    }
}
