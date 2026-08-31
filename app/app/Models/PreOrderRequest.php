<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreOrderRequest extends Model
{
    protected $fillable = ['user_id', 'query', 'category', 'brand'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
