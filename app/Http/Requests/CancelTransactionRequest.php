<?php

namespace App\Http\Requests;

class CancelTransactionRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3'],
        ];
    }
}
