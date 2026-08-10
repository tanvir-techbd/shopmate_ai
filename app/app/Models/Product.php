<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['canonical_title', 'category', 'brand', 'description', 'attributes', 'embedding'];

    protected $casts = [
        'attributes' => 'array',
        'embedding' => 'array',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function cheapestPrice(): ?ProductPrice
    {
        return $this->prices()->where('in_stock', true)->orderBy('price')->first();
    }

    /**
     * Folds this product's store listings into $winner (skipping any store
     * $winner already has a listing from, on the assumption $winner's own
     * listing is authoritative) and deletes this product. Used by the
     * admin duplicate-review queue - see docs/ENRICHMENT_ROADMAP.md
     * Phase B stage 3.
     */
    public function mergeInto(Product $winner): void
    {
        $winnerStoreIds = $winner->prices()->pluck('store_id');

        $this->prices()
            ->whereNotIn('store_id', $winnerStoreIds)
            ->update(['product_id' => $winner->id]);

        $this->delete();
    }
}
