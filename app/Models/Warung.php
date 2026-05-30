<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warung extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'address', 'phone', 'email', 'logo_url',
        'tax_percentage', 'is_tax_enabled', 'receipt_footer', 'currency',
    ];

    protected function casts(): array
    {
        return [
            'is_tax_enabled' => 'boolean',
            'tax_percentage' => 'decimal:2',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
