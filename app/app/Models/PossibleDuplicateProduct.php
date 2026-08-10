<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PossibleDuplicateProduct extends Model
{
    protected $fillable = ['product_a_id', 'product_b_id', 'similarity_score', 'status'];

    protected $casts = [
        'similarity_score' => 'decimal:3',
    ];

    public function productA(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_a_id');
    }

    public function productB(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_b_id');
    }
}
