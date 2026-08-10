<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    protected $fillable = [
        'product_id', 'store_id', 'store_title', 'price', 'delivery_charge',
        'rating', 'review_count', 'in_stock', 'product_url', 'last_checked_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'rating' => 'decimal:2',
        'in_stock' => 'boolean',
        'last_checked_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function getTotalCostAttribute(): float
    {
        return (float) $this->price + (float) $this->delivery_charge;
    }
}
