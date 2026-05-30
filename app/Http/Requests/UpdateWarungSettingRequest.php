<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWarungSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'tax_percentage' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'is_tax_enabled' => ['sometimes', 'boolean'],
            'receipt_footer' => ['nullable', 'string'],
        ];
    }
}
