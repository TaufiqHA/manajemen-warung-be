<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Warung;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request)
    {
        DB::beginTransaction();
        try {
            // Buat warung
            $warung = Warung::create([
                'name' => $request->warung_name,
            ]);

            // Buat user owner
            $user = User::create([
                'warung_id' => $warung->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'role' => 'OWNER',
                'is_active' => true,
            ]);

            DB::commit();

            // Token expire dalam 7 hari
            $tokenResult = $user->createToken('auth_token', ['*'], now()->addDays(7));

            $data = [
                'token' => $tokenResult->plainTextToken,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'warung_id' => $user->warung_id,
                ],
            ];

            return $this->successResponse($data, 'Registrasi berhasil', 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse('Terjadi kesalahan pada server', ['server' => [$e->getMessage()]], 500);
        }
    }

    public function login(LoginRequest $request)
    {
        $loginField = $request->has('username') ? 'username' : 'email';
        $loginValue = $request->input($loginField);

        if (! Auth::attempt([$loginField => $loginValue, 'password' => $request->password])) {
            return response()->json([
                'success' => false,
                'message' => 'Username atau password salah'
            ], 401);
        }

        $user = User::where($loginField, $loginValue)->firstOrFail();

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun anda telah dinonaktifkan.'
            ], 403);
        }

        // Generate token expire dalam 7 hari
        $tokenResult = $user->createToken('auth_token', ['*'], now()->addDays(7));

        $roleMap = [
            'OWNER' => 'OWNER',
            'ADMIN_TOKO' => 'ADMIN_TOKO',
            'ADMIN_KANTOR' => 'ADMIN_KANTOR',
        ];
        $formattedRole = $roleMap[strtoupper($user->role)] ?? $user->role;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'token' => $tokenResult->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $tokenResult->accessToken->expires_at,
                'user' => [
                    'id' => 'USR-' . str_pad($user->id, 3, '0', STR_PAD_LEFT),
                    'name' => $user->name,
                    'username' => $user->username ?? $user->email,
                    'role' => $formattedRole,
                    'email' => $user->email,
                ]
            ]
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }

    public function refresh(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();

        $tokenResult = $user->createToken('auth_token', ['*'], now()->addDays(7));

        $data = [
            'token' => $tokenResult->plainTextToken,
            'expires_at' => $tokenResult->accessToken->expires_at,
        ];

        return $this->successResponse($data, 'Token berhasil diperbarui');
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return $this->errorResponse('Password lama tidak sesuai', ['current_password' => ['Password lama yang Anda masukkan salah.']], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        // Hapus SEMUA token aktif sebelumnya
        $user->tokens()->delete();

        // Buat token baru
        $tokenResult = $user->createToken('auth_token', ['*'], now()->addDays(7));

        $data = [
            'token' => $tokenResult->plainTextToken,
        ];

        return $this->successResponse($data, 'Password berhasil diubah, silahkan simpan token baru Anda');
    }
}
