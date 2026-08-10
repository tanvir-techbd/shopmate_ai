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
 */
class ProductMatchingService
{
    private const AUTO_MERGE_THRESHOLD = 0.55;
    private const BRAND_MATCH_BOOST = 0.30;

    private const STOPWORDS = [
        'the', 'and', 'for', 'with', 'a', 'an', 'by', 'of', 'in', 'to', 'on',
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

        foreach ($candidateProducts as $candidate) {
            $score = $this->similarity($listing, $candidate);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $candidate;
            }
        }

        if ($best !== null && $bestScore >= self::AUTO_MERGE_THRESHOLD) {
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

    private function tokenize(string $text): Collection
    {
        preg_match_all('/[a-z0-9]+/', strtolower($text), $matches);

        return collect($matches[0])
            ->filter(fn (string $token) => strlen($token) > 2 && ! in_array($token, self::STOPWORDS, true))
            ->unique()
            ->values();
    }
}
