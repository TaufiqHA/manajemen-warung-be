<?php

namespace App\Http\Requests;

class StoreExpenseRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:1'],
            'jumlah' => ['nullable', 'numeric', 'min:1'],
            'category' => ['nullable', 'string'],
            'kategori' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'catatan' => ['nullable', 'string'],
            'date' => ['nullable', 'string'],
            'tanggal' => ['nullable', 'string'],
        ];
    }
}
