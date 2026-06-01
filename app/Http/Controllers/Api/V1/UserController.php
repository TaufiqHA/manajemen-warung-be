<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();

        $query = User::where('warung_id', $user->warung_id);

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        $perPage = $request->input('per_page', 20);
        $users = $query->paginate($perPage);

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request)
    {
        $authUser = $request->user();

        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => $request->role,
            'warung_id' => $authUser->warung_id,
        ]);

        return $this->successResponse(new UserResource($newUser), 'User berhasil ditambahkan', 201);
    }

    public function update(UpdateUserRequest $request, $id)
    {
        $authUser = $request->user();

        $targetUser = User::where('warung_id', $authUser->warung_id)->findOrFail($id);

        if (in_array($authUser->role, ['ADMIN_TOKO', 'ADMIN_KANTOR']) && $targetUser->role === 'OWNER') {
            return $this->errorResponse('Admin tidak bisa mengedit Owner.', null, 403);
        }

        $targetUser->update($request->validated());

        return $this->successResponse(new UserResource($targetUser), 'User berhasil diperbarui');
    }

    public function destroy(Request $request, $id)
    {
        $authUser = $request->user();

        if ($authUser->id == $id) {
            return $this->errorResponse('Tidak bisa menghapus diri sendiri.', null, 400);
        }

        $targetUser = User::where('warung_id', $authUser->warung_id)->findOrFail($id);
        $targetUser->delete();

        return $this->successResponse(null, 'Berhasil dihapus.');
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $user->update($request->only('name', 'phone'));

        return $this->successResponse(new UserResource($user->load('warung')), 'Profil berhasil diperbarui');
    }
}
