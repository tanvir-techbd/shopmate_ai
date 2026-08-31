<?php

namespace App\StoreProviders;

/**
 * Fetches REAL listings from othoba.com via their own search backend
 * (Typesense, at search.othoba.com) instead of scraping rendered HTML -
 * see docs/ENRICHMENT_ROADMAP.md Phase C-lite.
 *
 * Why this instead of HTML scraping: othoba.com's own frontend embeds a
 * "search-only" Typesense API key directly in its page JS and calls this
 * exact endpoint from the browser to power its search box - that's the
 * whole point of a search-only key in Typesense's design (read-only,
 * rate-limited, meant to be public). Calling it directly is doing exactly
 * what a visitor's browser does, not bypassing anything: no login, no
 * CAPTCHA, no anti-bot defeat, no headless browser. `search.othoba.com`
 * has no robots.txt at all (404), so there's nothing there to violate
 * either. This also fixes a real problem an earlier HTML-scraping version
 * of this provider had: the guessed `/catalogsearch/result/?q=` URL
 * turned out to return generic homepage widgets, not real query-matched
 * results (confirmed by checking the response's title/H1) - this API is
 * the actual mechanism their search box uses, so results are genuinely
 * query-relevant, with clean structured brand/category/price/stock
 * fields instead of guessed ones.
 *
 * Still: this is scraping a public API meant for their own frontend, not
 * an official partner integration - keep this to personal/educational MVP
 * use with a light request pattern, and verify Othoba's current ToS
 * before any wider or higher-volume use.
 */
class OthobaLiveProvider implements StoreProviderInterface
{
    private const SITE_URL = 'https://www.othoba.com';
    private const SEARCH_HOST = 'https://search.othoba.com';
    private const COLLECTION = 'Products';

    /**
     * Only what's listed here ever enters the catalogue - a query for
     * anything outside these terms/categories will never find a match
     * regardless of how good search.py's ranking is, since the product
     * simply was never imported. "noodles" was the first reported gap:
     * othoba.com genuinely has 274 noodle listings, ShopMate just never
     * asked for any groceries. Widen this list (and nlp.py's
     * CATEGORY_SYNONYMS, kept in step with it) when another common,
     * clearly-absent category turns up the same way, rather than chasing
     * single missing products one at a time.
     */
    private const SEED_QUERIES = [
        'backpack' => 'Bags',
        'smartphone' => 'Smartphones',
        'laptop' => 'Laptops',
        'earbuds' => 'Audio',
        'sneakers' => 'Footwear',
        'watch' => 'Watches',
        'kettle' => 'Home Appliances',
        'smart band' => 'Wearables',
        'umbrella' => 'Accessories',
        'noodles' => 'Groceries',
        'rice' => 'Groceries',
        'cooking oil' => 'Groceries',
        'shampoo' => 'Personal Care',
        'toothpaste' => 'Personal Care',
        'perfume' => 'Personal Care',
        'toy' => 'Toys',
        'blender' => 'Home Appliances',
        't-shirt' => 'Fashion',
        'saree' => 'Fashion',
        'sunglasses' => 'Accessories',
        'wallet' => 'Accessories',
    ];

    /**
     * Our full category taxonomy - kept in step with nlp.py's
     * CATEGORY_SYNONYMS values. Used by hitToListing() to decide whether
     * a listing's own Othoba category is trustworthy enough to override a
     * SEED_QUERIES search term's assumed category - see that method's
     * docblock for why that override exists.
     */
    private const KNOWN_CATEGORIES = [
        'Bags', 'Smartphones', 'Laptops', 'Audio', 'Footwear', 'Watches',
        'Home Appliances', 'Fashion', 'Wearables', 'Accessories', 'Groceries',
        'Personal Care', 'Toys',
    ];

    private const RESULTS_PER_QUERY = 15;

    /** Seconds to wait between requests - self-imposed politeness. */
    private const REQUEST_DELAY_SECONDS = 1;

    public function slug(): string
    {
        return 'othoba';
    }

    public function name(): string
    {
        return 'Othoba.com';
    }

    public function baseUrl(): ?string
    {
        return self::SITE_URL;
    }

    public function origin(): string
    {
        return 'domestic';
    }

    public function fetchListings(): array
    {
        $listings = [];
        $seenProductIds = [];
        $first = true;

        foreach (self::SEED_QUERIES as $query => $category) {
            if (! $first) {
                sleep(self::REQUEST_DELAY_SECONDS);
            }
            $first = false;

            foreach ($this->searchFor($query, $category) as $listing) {
                $id = $listing['_product_id'];
                unset($listing['_product_id']);

                if (isset($seenProductIds[$id])) {
                    continue; // same product can rank for more than one seed query
                }
                $seenProductIds[$id] = true;

                $listings[] = $listing;
            }
        }

        return $listings;
    }

    /**
     * On-demand search for whatever the user actually typed, not the fixed
     * SEED_QUERIES list - see LiveSearchFallbackService, which calls this
     * only once local search has already come back empty. Each listing is
     * categorised from Othoba's own CategoryName (normalizeCategory())
     * rather than a category we chose in advance, since there is no way
     * to know ahead of time which of ours (if any) an arbitrary query
     * belongs to.
     */
    public function liveSearch(string $query): array
    {
        $listings = [];
        $seen = [];

        foreach ($this->searchFor($query, null) as $listing) {
            $id = $listing['_product_id'];
            unset($listing['_product_id']);

            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;

            $listings[] = $listing;
        }

        return $listings;
    }

    private function searchFor(string $query, ?string $category): array
    {
        $url = self::SEARCH_HOST.'/collections/'.self::COLLECTION.'/documents/search'
            .'?q='.urlencode($query)
            .'&query_by=TagName,Sku,Name'
            .'&per_page='.self::RESULTS_PER_QUERY;

        $response = $this->httpGet($url);
        if ($response === null) {
            return [];
        }

        $data = json_decode($response, true);
        if (! is_array($data) || empty($data['hits'])) {
            return [];
        }

        $listings = [];
        foreach ($data['hits'] as $hit) {
            $listing = $this->hitToListing($hit['document'] ?? [], $category);
            if ($listing !== null) {
                $listings[] = $listing;
            }
        }

        return $listings;
    }

    private function hitToListing(array $doc, ?string $categoryHint): ?array
    {
        $title = trim($doc['Name'] ?? '');
        $price = (float) ($doc['Price'] ?? 0);
        $seName = trim($doc['SeName'] ?? '');

        if ($title === '' || $price <= 0 || $seName === '') {
            return null;
        }

        // A SEED_QUERIES search term is a hint, not a guarantee: Othoba's
        // own search matches on tags/SKU/name too, so a "rice" search can
        // genuinely return a rice cooker (an appliance) alongside actual
        // rice (groceries) - forcing every hit for that term into the
        // same category mislabelled whichever one didn't match, which is
        // exactly why a real, already-imported rice cooker couldn't be
        // found by a "rice cooker" search (it was tagged "Groceries").
        // Prefer the listing's OWN category whenever it confidently
        // normalises to something we recognise; only fall back to the
        // search term's hint when Othoba's own category doesn't.
        $ownCategory = $this->normalizeCategory($doc['CategoryName'][0] ?? null);
        $category = in_array($ownCategory, self::KNOWN_CATEGORIES, true)
            ? $ownCategory
            : ($categoryHint ?? $ownCategory);

        // Real listing titles (SEO-stuffed ones especially) can run long;
        // truncate defensively regardless of how wide the DB column is.
        if (mb_strlen($title) > 480) {
            $title = mb_substr($title, 0, 477).'...';
        }

        $totalReviews = (int) ($doc['TotalReviews'] ?? 0);
        $ratingSum = (float) ($doc['RatingSum'] ?? 0);
        $rating = $totalReviews > 0 ? round(min(5.0, $ratingSum / $totalReviews), 1) : null;

        $manufacturer = $doc['ManufacturerName'][0] ?? null;
        $brand = ($manufacturer && strcasecmp($manufacturer, 'No Brand') !== 0) ? $manufacturer : null;

        // Same public search response already used for title/price/stock -
        // their own frontend renders this exact field as the thumbnail in
        // its search-suggestion dropdown, so it's meant to be displayed,
        // not an internal-only asset. Absent on some listings; left null
        // rather than guessed at.
        $imageUrl = trim($doc['AutoCompleteImageUrl'] ?? '') ?: null;

        return [
            '_product_id' => $doc['ProductId'] ?? $seName,
            'title' => $title,
            'price' => $price,
            'delivery_charge' => 0, // not exposed by this endpoint; resolved at checkout on the real site
            'rating' => $rating,
            'review_count' => $totalReviews,
            'in_stock' => ! ($doc['SoldOut'] ?? false),
            'brand' => $brand,
            'category' => $category,
            'description' => null,
            'attributes' => [],
            'product_url' => self::SITE_URL.'/'.$seName,
            'image_url' => $imageUrl,
        ];
    }

    /**
     * Maps Othoba's own free-text category name onto one of ours when it
     * obviously corresponds (so structured category filtering in
     * search.py's _apply_filters() still applies to it), otherwise keeps
     * Othoba's own name rather than dropping the product entirely - TF-IDF
     * relevance still works fine against an unrecognised category string,
     * it just won't get the hard-filter benefit nlp.py's synonym list
     * gives to a recognised one.
     */
    private function normalizeCategory(?string $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        static $known = [
            'luggage' => 'Bags', 'bag' => 'Bags',
            'smartphone' => 'Smartphones', 'mobile' => 'Smartphones',
            'laptop' => 'Laptops', 'notebook' => 'Laptops',
            'earbud' => 'Audio', 'headphone' => 'Audio', 'speaker' => 'Audio', 'audio' => 'Audio',
            'footwear' => 'Footwear', 'shoe' => 'Footwear', 'sneaker' => 'Footwear', 'sandal' => 'Footwear',
            'watch' => 'Watches',
            'appliance' => 'Home Appliances', 'kitchen' => 'Home Appliances', 'cooker' => 'Home Appliances',
            'fashion' => 'Fashion', 'saree' => 'Fashion', 'cloth' => 'Fashion', 'apparel' => 'Fashion',
            'wearable' => 'Wearables', 'fitness' => 'Wearables', 'smart band' => 'Wearables',
            'accessor' => 'Accessories',
            'grocery' => 'Groceries', 'food' => 'Groceries', 'noodle' => 'Groceries', 'snack' => 'Groceries', 'daily bazar' => 'Groceries',
            'beauty' => 'Personal Care', 'cosmetic' => 'Personal Care', 'personal care' => 'Personal Care',
            'toy' => 'Toys', 'kids' => 'Toys',
        ];

        $lower = strtolower($raw);
        foreach ($known as $needle => $mapped) {
            if (str_contains($lower, $needle)) {
                return $mapped;
            }
        }

        return $raw;
    }

    private function httpGet(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'header' => 'X-TYPESENSE-API-KEY: '.config('services.othoba.typesense_key')."\r\n",
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        return $response === false ? null : $response;
    }
}
