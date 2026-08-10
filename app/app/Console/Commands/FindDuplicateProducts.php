<?php

namespace App\Console\Commands;

use App\Models\PossibleDuplicateProduct;
use App\Models\Product;
use App\Services\ProductMatchingService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Retroactive safety net, separate from the live matching that
 * `providers:import` does on ingestion (see
 * docs/ENRICHMENT_ROADMAP.md Phase B stage 3): pairwise-compares existing
 * products within each category and flags anything similar enough to be
 * worth a human look, without ever auto-merging - merging existing,
 * possibly-already-diverged product records is an admin decision, not an
 * automated one.
 */
#[Signature('products:find-duplicates')]
#[Description('Flag pairs of existing products that look like the same item, for admin review.')]
class FindDuplicateProducts extends Command
{
    private const REVIEW_MIN = 0.30;

    public function handle(ProductMatchingService $matcher): int
    {
        $flagged = 0;

        Product::whereNotNull('category')
            ->get()
            ->groupBy('category')
            ->each(function ($products) use ($matcher, &$flagged) {
                $products = $products->values();

                for ($i = 0; $i < $products->count(); $i++) {
                    for ($j = $i + 1; $j < $products->count(); $j++) {
                        $a = $products[$i];
                        $b = $products[$j];

                        $score = $matcher->similarity(
                            ['title' => $a->canonical_title, 'brand' => $a->brand],
                            $b,
                        );

                        if ($score < self::REVIEW_MIN) {
                            continue;
                        }

                        [$lowId, $highId] = $a->id < $b->id ? [$a->id, $b->id] : [$b->id, $a->id];

                        $exists = PossibleDuplicateProduct::where('product_a_id', $lowId)
                            ->where('product_b_id', $highId)
                            ->exists();

                        if ($exists) {
                            continue;
                        }

                        PossibleDuplicateProduct::create([
                            'product_a_id' => $lowId,
                            'product_b_id' => $highId,
                            'similarity_score' => round($score, 3),
                            'status' => 'pending',
                        ]);

                        $flagged++;
                    }
                }
            });

        $this->info("Flagged {$flagged} possible duplicate pair(s) for review.");

        return self::SUCCESS;
    }
}
