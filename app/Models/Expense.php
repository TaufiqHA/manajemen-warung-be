<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'warung_id',
        'created_by',
        'title',
        'amount',
        'category',
        'note',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function warung(): BelongsTo
    {
        return $this->belongsTo(Warung::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
