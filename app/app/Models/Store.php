<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $fillable = ['name', 'slug', 'base_url', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }
}
