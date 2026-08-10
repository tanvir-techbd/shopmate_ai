<?php

namespace App\StoreProviders;

/**
 * Fetches REAL listings from othoba.com's public, unauthenticated search
 * results (server-rendered HTML, no JS execution needed) - see
 * docs/ENRICHMENT_ROADMAP.md Phase C-lite.
 *
 * Scope, deliberately: this only ever GETs public search-result pages that
 * robots.txt does not disallow (checked manually - /catalogsearch/result/
 * is not listed; a different, older /search? endpoint is, and is not
 * used here). No login, no CAPTCHA, no anti-bot bypass, no headless
 * browser. That said, robots.txt permission is not the same as full ToS
 * compliance - this is meant for personal/educational MVP use with a
 * light, rate-limited request pattern, not production-scale traffic.
 * Verify Othoba's current terms before any wider or higher-volume use.
 *
 * There's no category taxonomy exposed on the search results themselves,
 * so each seed query below is mapped to one of ShopMate's own categories
 * by hand - the same category-per-query approach a real integration would
 * likely start with before something richer is available.
 */
class OthobaLiveProvider implements StoreProviderInterface
{
    private const BASE_URL = 'https://www.othoba.com';

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
    ];

    /** Seconds to wait between requests - self-imposed politeness, robots.txt specifies no crawl-delay. */
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
        return self::BASE_URL;
    }

    public function fetchListings(): array
    {
        $listings = [];
        $first = true;

        foreach (self::SEED_QUERIES as $query => $category) {
            if (! $first) {
                sleep(self::REQUEST_DELAY_SECONDS);
            }
            $first = false;

            $listings = [...$listings, ...$this->fetchForQuery($query, $category)];
        }

        return $listings;
    }

    private function fetchForQuery(string $query, string $category): array
    {
        $html = $this->httpGet(self::BASE_URL.'/catalogsearch/result/?q='.urlencode($query));

        if ($html === null) {
            return [];
        }

        return $this->parseListings($html, $category);
    }

    private function parseListings(string $html, string $category): array
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $cards = $xpath->query(
            "//div[contains(concat(' ', normalize-space(@class), ' '), ' product ') and .//div[contains(@class, 'product-price')]]"
        );

        $listings = [];
        $seenUrls = [];

        foreach ($cards as $card) {
            $nameNode = $xpath->query(".//h4[contains(@class,'product-name')]/a", $card)->item(0);
            $priceNode = $xpath->query(".//ins[contains(@class,'new-price')]", $card)->item(0);

            if (! $nameNode || ! $priceNode) {
                continue;
            }

            $title = trim($nameNode->textContent);
            $price = $this->parsePrice($priceNode->textContent);
            $href = trim($nameNode->getAttribute('href'));

            if ($title === '' || $price === null || $href === '') {
                continue;
            }

            $productUrl = str_starts_with($href, 'http') ? $href : self::BASE_URL.'/'.ltrim($href, '/');

            if (isset($seenUrls[$productUrl])) {
                continue; // same product can appear in more than one widget on the page
            }
            $seenUrls[$productUrl] = true;

            $reviewNode = $xpath->query(".//a[contains(@class,'rating-reviews')]", $card)->item(0);
            $reviewCount = 0;
            if ($reviewNode && preg_match('/(\d+)/', $reviewNode->textContent, $m)) {
                $reviewCount = (int) $m[1];
            }

            $ratingNode = $xpath->query(".//span[contains(@class,'ratings') and not(contains(@class,'ratings-full'))]", $card)->item(0);
            $rating = null;
            if ($ratingNode && preg_match('/width:\s*(\d+)/', $ratingNode->getAttribute('style'), $m) && (int) $m[1] > 0) {
                $rating = round(((int) $m[1]) / 20, 1); // 0-100% -> 0-5 stars
            }

            $listings[] = [
                'title' => $title,
                'price' => $price,
                'delivery_charge' => 0, // not shown on search results; resolved at checkout on the real site
                'rating' => $rating,
                'review_count' => $reviewCount,
                'in_stock' => true, // no reliable out-of-stock marker found on search cards; refine if one turns up
                'brand' => $this->guessBrand($title),
                'category' => $category,
                'description' => null,
                'attributes' => [],
                'product_url' => $productUrl,
            ];
        }

        return $listings;
    }

    private function parsePrice(string $text): ?float
    {
        return preg_match('/[\d,]+/', $text, $m) ? (float) str_replace(',', '', $m[0]) : null;
    }

    /**
     * Othoba's search results don't expose a structured brand field, so
     * this takes the first word of the title as a best-effort guess (most
     * of their listings lead with the brand, e.g. "Vision Air Fryer...").
     * ProductMatchingService already tolerates a missing/wrong brand by
     * falling back to title similarity alone, so a wrong guess here just
     * means one less signal, not a broken match.
     */
    private function guessBrand(string $title): ?string
    {
        $firstWord = strtok($title, ' ');

        return $firstWord !== false ? $firstWord : null;
    }

    private function httpGet(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36\r\n",
                'ignore_errors' => true,
            ],
        ]);

        $html = @file_get_contents($url, false, $context);

        return $html === false ? null : $html;
    }
}
