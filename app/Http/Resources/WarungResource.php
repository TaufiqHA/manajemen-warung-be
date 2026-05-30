<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarungResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'logo_url' => $this->logo_url ? url($this->logo_url) : null,
            'tax_percentage' => (float) $this->tax_percentage,
            'is_tax_enabled' => (bool) $this->is_tax_enabled,
            'receipt_footer' => $this->receipt_footer,
            'currency' => 'IDR', // Default format sesuai konteks
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
