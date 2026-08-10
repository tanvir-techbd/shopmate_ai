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
}
