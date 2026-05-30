<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_code' => $this->transaction_code,
            'total_amount' => $this->total_amount,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'grand_total' => $this->grand_total,
            'payment_method' => $this->payment_method,
            'paid_amount' => $this->paid_amount,
            'change_amount' => $this->change_amount,
            'status' => $this->status,
            'cashier_id' => $this->cashier_id,
            'cashier_name' => $this->whenLoaded('cashier', fn () => $this->cashier->name),
            'note' => $this->note,
            'cancel_reason' => $this->cancel_reason,
            'cancelled_at' => $this->cancelled_at,
            'created_at' => $this->created_at,
            'items' => TransactionItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
