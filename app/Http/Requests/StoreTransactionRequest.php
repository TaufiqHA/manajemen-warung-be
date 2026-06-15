<?php

namespace App\Http\Requests;

class StoreTransactionRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idTransaksi' => ['nullable', 'string'],
            'waktu' => ['nullable', 'string'],
            'dicatatOleh' => ['nullable', 'string'],
            'customerName' => ['nullable', 'string'],
            'orderStatus' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.jumlah' => ['nullable', 'integer', 'min:1'],
            'items.*.namaItem' => ['nullable', 'string'],
            'items.*.harga' => ['nullable', 'numeric'],
            'items.*.catatan' => ['nullable', 'string'],
            'items.*.servedQty' => ['nullable', 'integer', 'min:0'],
            'payment_method' => ['nullable', 'in:CASH,TRANSFER,QRIS'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
        ];
    }
}
