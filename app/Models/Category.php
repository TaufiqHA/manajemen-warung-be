<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'warung_id',
        'name',
        'description',
        'icon',
    ];

    public function warung(): BelongsTo
    {
        return $this->belongsTo(Warung::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
