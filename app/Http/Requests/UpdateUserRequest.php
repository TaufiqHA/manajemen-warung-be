<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'username' => ['sometimes', 'nullable', 'string', 'max:255'],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['sometimes', 'required', 'in:OWNER,ADMIN_TOKO,ADMIN_KANTOR'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
