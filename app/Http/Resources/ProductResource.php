<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $categoryName = $this->category ? $this->category->name : 'Lainnya';

        $hasSales = $this->has_sales ?? null;
        if ($hasSales === null && $this->relationLoaded('transactionItems')) {
            $hasSales = $this->transactionItems->isNotEmpty();
        }
        if ($hasSales === null) {
            $hasSales = $this->transactionItems()->exists();
        }

        return [
            'id' => 'PRD-'.str_pad($this->id, 3, '0', STR_PAD_LEFT),
            'name' => $this->name,
            'category' => $categoryName,
            'price' => (float) $this->price,
            'stock' => (int) $this->stock,
            'imageUrl' => $this->image_url ? (str_starts_with($this->image_url, 'http') ? $this->image_url : url($this->image_url)) : null,
            // Keep the fields below to ensure existing tests pass
            'image_url' => $this->image_url ? url($this->image_url) : null,
            'category_id' => $this->category_id,
            'category_name' => $categoryName,
            'is_active' => $this->is_active,
            // True bila produk sudah pernah masuk ke transaksi (tidak boleh dihapus).
            'has_sales' => (bool) $hasSales,
        ];
    }
}
