<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'warung_id',
        'cashier_id',
        'transaction_code',
        'total_amount',
        'discount_amount',
        'tax_amount',
        'grand_total',
        'payment_method',
        'paid_amount',
        'change_amount',
        'status',
        'note',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
    ];

    public function warung(): BelongsTo
    {
        return $this->belongsTo(Warung::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }
}
