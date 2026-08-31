<?php

namespace App\StoreProviders;

/**
 * Common shape every store data source implements - mock fixture today,
 * a Daraz/Pickaboo/etc. affiliate feed parser later (see
 * docs/ENRICHMENT_ROADMAP.md Phase C). Nothing downstream (matching,
 * search, ranking) needs to know which kind of provider it's looking at.
 */
interface StoreProviderInterface
{
    public function slug(): string;

    public function name(): string;

    public function baseUrl(): ?string;

    /**
     * 'domestic' or 'international' - lets search optionally exclude
     * cross-border stores per user preference (see
     * User::include_international_stores and ai-service's
     * QueryRequest.include_international). Every current provider (the
     * mock Bangladeshi stores and the real Othoba.com feed) is domestic;
     * the first international one is the mock GlobalDeals Express fixture.
     */
    public function origin(): string;

    /**
     * @return array<int, array{
     *     title: string, price: float, delivery_charge: float,
     *     rating: ?float, review_count: int, in_stock: bool,
     *     brand: ?string, category: ?string, description: ?string,
     *     attributes: array, product_url?: string
     * }>
     */
    public function fetchListings(): array;
}
