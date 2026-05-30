<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiRequest;

class RegisterRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'warung_name' => ['required', 'string', 'max:255'],
        ];
    }
}
