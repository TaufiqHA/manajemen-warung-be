<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateWarungSettingRequest;
use App\Http\Requests\WarungLogoRequest;
use App\Http\Resources\WarungResource;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WarungSettingController extends Controller
{
    use ApiResponse;

    public function show(Request $request)
    {
        $warung = $request->user()->warung;

        return $this->successResponse(new WarungResource($warung), 'Data warung berhasil diambil');
    }

    public function update(UpdateWarungSettingRequest $request)
    {
        $warung = $request->user()->warung;
        $warung->update($request->validated());

        return $this->successResponse(new WarungResource($warung), 'Data warung berhasil diperbarui');
    }

    public function uploadLogo(WarungLogoRequest $request)
    {
        $warung = $request->user()->warung;

        if ($warung->logo_url) {
            Storage::disk('public')->delete(str_replace('storage/', '', $warung->logo_url));
        }

        $path = $request->file('logo')->store('logos', 'public');
        $warung->update(['logo_url' => 'storage/'.$path]);

        return $this->successResponse(['logo_url' => url('storage/'.$path)], 'Logo berhasil diunggah');
    }
}
