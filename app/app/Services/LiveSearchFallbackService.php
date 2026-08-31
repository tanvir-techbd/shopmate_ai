<?php

namespace App\Services;

use App\StoreProviders\OthobaLiveProvider;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The pre-imported catalogue only ever knows about what
 * OthobaLiveProvider::SEED_QUERIES explicitly asked Othoba for - a query
 * for anything else (e.g. "noodles" before it was added) came back "not
 * available" even though the real store carries it. This makes one live,
 * on-demand Othoba search for the *exact* query the user typed, so
 * ChatController::send() can retry the AI service and actually find it.
 *
 * Deliberately narrow in scope to keep the existing "search is instant"
 * promise for the common case: this only ever runs when local search has
 * already come back completely empty (see ChatController::send()), so a
 * normal, already-served-locally search never pays this extra round trip.
 * Never throws - any failure (Othoba unreachable, malformed response)
 * degrades to "no fallback found", same as if nothing had been tried.
 *
 * Skips the possible-duplicate scan the batch import commands run
 * (products:find-duplicates) - that's a full pairwise catalogue scan, fine
 * for a periodic CLI job, not for a synchronous web request. It still
 * picks up anything this leaves behind next time that command runs.
 */
class LiveSearchFallbackService
{
    public function __construct(
        private readonly OthobaLiveProvider $provider,
        private readonly ListingIngestService $ingest,
    ) {
    }

    /**
     * @return bool true if at least one listing was ingested - the caller
     *              should re-query the AI service to pick it up.
     */
    public function tryFor(string $query): bool
    {
        try {
            $listings = $this->provider->liveSearch($query);
            if (empty($listings)) {
                return false;
            }

            $store = $this->ingest->findOrCreateStore($this->provider);
            $result = $this->ingest->ingest($store, $listings);

            return $result['listings'] > 0;
        } catch (Throwable $e) {
            Log::warning('LiveSearchFallbackService: live Othoba search failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
