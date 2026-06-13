<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $roleMap = [
            'OWNER' => 'OWNER',
            'ADMIN_TOKO' => 'ADMIN_TOKO',
            'ADMIN_KANTOR' => 'ADMIN_KANTOR',
        ];
        $formattedRole = $roleMap[strtoupper($this->role)] ?? strtoupper($this->role);

        return [
            'id' => 'USR-'.str_pad($this->id, 3, '0', STR_PAD_LEFT),
            'name' => $this->name,
            'username' => $this->username ?? $this->email,
            'role' => $formattedRole,
            'email' => $this->email, // keep for backward compatibility with existing tests
        ];
    }
}
