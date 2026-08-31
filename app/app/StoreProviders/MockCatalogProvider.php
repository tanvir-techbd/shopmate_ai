<?php

namespace App\StoreProviders;

class MockCatalogProvider implements StoreProviderInterface
{
    public function __construct(
        private readonly string $slug,
        private readonly string $name,
        private readonly ?string $baseUrl,
        private readonly string $origin = 'domestic',
    ) {
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function baseUrl(): ?string
    {
        return $this->baseUrl;
    }

    public function origin(): string
    {
        return $this->origin;
    }

    public function fetchListings(): array
    {
        return ProviderCatalogFixture::forStore($this->slug);
    }
}
