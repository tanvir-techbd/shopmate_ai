<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceAlert extends Model
{
    protected $fillable = ['user_id', 'product_id', 'target_price', 'type', 'is_active', 'triggered_at'];

    protected $casts = [
        'target_price' => 'decimal:2',
        'is_active' => 'boolean',
        'triggered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
