<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Decides whether an incoming raw store listing is the same product as one
 * already in the catalogue, or a new product - see
 * docs/ENRICHMENT_ROADMAP.md Phase B. This is the piece that makes
 * multi-store comparison real: without it, "cross-store matching" is just
 * whatever a human hand-paired when writing fixture data.
 *
 * Deliberately simple and dependency-free (token-set Jaccard similarity,
 * no ML model) so it runs instantly on a low-spec laptop - the documented
 * upgrade path is swapping this for embedding-based similarity once TF-IDF
 * search hits the same ceiling (see IMPLEMENTATION_PLAN.md §1).
 *
 * Tuned against two different test sets: a clean hand-crafted 3-store mock
 * fixture (varied phrasing, distinct brands - see
 * app/StoreProviders/ProviderCatalogFixture.php), and real listings pulled
 * live from Othoba.com (many SKUs per brand, SEO-stuffed titles). The mock
 * fixture alone hid a real precision bug: on real data, "Samsung Galaxy
 * A17" and "Samsung Galaxy A07" - genuinely different phones - scored
 * above the original 0.55 auto-merge threshold purely on brand match +
 * generic shared words ("galaxy", "ram", "rom"). The model-code veto and
 * threshold below exist specifically to fix that without breaking the
 * mock fixture's validated 100% recall.
 */
class ProductMatchingService
{
    private const AUTO_MERGE_THRESHOLD = 0.65;
    private const BRAND_MATCH_BOOST = 0.30;

    /**
     * When neither listing has a usable brand, there's zero corroborating
     * identity signal beyond raw text - and real generic/unbranded
     * listings (umbrellas, charging cables, screen protectors from small
     * sellers) often share so much templated boilerplate phrasing
     * ("... Magnetic Charging Cable High Quality USB Charger Cable ... for
     * X Smart Watch (Black)") that plain title overlap alone comfortably
     * clears 0.65 despite being different products. Require much stronger
     * evidence in that specific case.
     */
    private const NO_BRAND_THRESHOLD = 0.80;

    private const STOPWORDS = [
        // generic connectors
        'the', 'and', 'for', 'with', 'a', 'an', 'by', 'of', 'in', 'to', 'on',
        // marketing/listing boilerplate - carries no product-identity signal,
        // but shows up often enough on real listings to inflate similarity
        // between genuinely different products (e.g. two unrelated phones
        // both titled "... (Best Price)")
        'best', 'price', 'new', 'original', 'official', 'genuine', 'offer',
        'sale', 'hot', 'bangladesh', 'bd', 'buy', 'online', 'shop',
    ];

    /**
     * Finds an existing product this listing is almost certainly the same
     * as (same category, and if both have a brand, an *exact* brand match
     * is required - two listings with clearly different named brands are
     * never auto-merged, no matter how similar the titles read), or
     * creates a new product from the listing.
     */
    public function matchOrCreate(array $listing, Collection $candidateProducts): Product
    {
        $best = null;
        $bestScore = 0.0;

        $bestHasBrand = false;

        foreach ($candidateProducts as $candidate) {
            $score = $this->similarity($listing, $candidate);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $candidate;
                $bestHasBrand = ($listing['brand'] ?? null) && $candidate->brand;
            }
        }

        $threshold = $bestHasBrand ? self::AUTO_MERGE_THRESHOLD : self::NO_BRAND_THRESHOLD;

        if ($best !== null && $bestScore >= $threshold) {
            return $best;
        }

        return Product::create([
            'canonical_title' => $listing['title'],
            'category' => $listing['category'] ?? null,
            'brand' => $listing['brand'] ?? null,
            'description' => $listing['description'] ?? null,
            'attributes' => $listing['attributes'] ?? [],
        ]);
    }

    public function similarity(array $listing, Product $candidate): float
    {
        $listingBrand = $listing['brand'] ?? null;
        $candidateBrand = $candidate->brand;
        $brandsKnownBoth = $listingBrand && $candidateBrand;

        if ($brandsKnownBoth && strcasecmp($listingBrand, $candidateBrand) !== 0) {
            return 0.0; // explicitly different named brands: never the same product
        }

        if ($this->modelCodesConflict($listing['title'], $candidate->canonical_title)) {
            return 0.0; // e.g. "Galaxy A17" vs "Galaxy A07" - same brand, different model
        }

        $titleScore = $this->tokenJaccard($listing['title'], $candidate->canonical_title);
        $brandBoost = $brandsKnownBoth ? self::BRAND_MATCH_BOOST : 0.0;

        return min(1.0, $titleScore + $brandBoost);
    }

    public function tokenJaccard(string $a, string $b): float
    {
        $tokensA = $this->tokenize($a);
        $tokensB = $this->tokenize($b);

        if ($tokensA->isEmpty() || $tokensB->isEmpty()) {
            return 0.0;
        }

        $intersection = $tokensA->intersect($tokensB)->count();
        $union = $tokensA->union($tokensB->all())->count();

        return $union > 0 ? $intersection / $union : 0.0;
    }

    /**
     * Model/variant codes such as "a17", "s24", "c100x" are a strong
     * identity signal in electronics - letters-then-digits(-then-letters),
     * which naturally excludes RAM/storage values like "128gb" (those are
     * digits-first) and plain model *names* like "Eagle"/"Power" (no
     * digits). If both titles contain at least one such code and none of
     * theirs match, they're not the same product regardless of brand or
     * word overlap. If a title has no such code, this never fires.
     */
    private function modelCodesConflict(string $a, string $b): bool
    {
        $codesA = $this->extractModelCodes($a);
        $codesB = $this->extractModelCodes($b);

        if ($codesA->isEmpty() || $codesB->isEmpty()) {
            return false;
        }

        return $codesA->intersect($codesB)->isEmpty();
    }

    private function extractModelCodes(string $text): Collection
    {
        preg_match_all('/\b[a-z]{1,3}\d{1,3}[a-z]{0,2}\b/i', strtolower($text), $matches);

        return collect($matches[0])->unique()->values();
    }

    private function tokenize(string $text): Collection
    {
        preg_match_all('/[a-z0-9]+/', strtolower($text), $matches);

        return collect($matches[0])
            ->filter(fn (string $token) => strlen($token) > 2 && ! in_array($token, self::STOPWORDS, true))
            ->unique()
            ->values();
    }
}
