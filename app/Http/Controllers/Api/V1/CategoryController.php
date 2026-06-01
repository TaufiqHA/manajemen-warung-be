<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();

        $categories = Category::withCount('products')
            ->where('warung_id', $user->warung_id)
            ->latest()
            ->get();

        return $this->successResponse(CategoryResource::collection($categories), 'Daftar kategori berhasil diambil');
    }

    public function store(StoreCategoryRequest $request)
    {
        $user = $request->user();

        $category = Category::create(array_merge($request->validated(), [
            'warung_id' => $user->warung_id,
        ]));

        return $this->successResponse(new CategoryResource($category), 'Kategori berhasil ditambahkan', 201);
    }

    public function update(UpdateCategoryRequest $request, $id)
    {
        $user = $request->user();

        $category = Category::where('warung_id', $user->warung_id)->findOrFail($id);
        $category->update($request->validated());

        return $this->successResponse(new CategoryResource($category), 'Kategori berhasil diperbarui');
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $category = Category::withCount('products')->where('warung_id', $user->warung_id)->findOrFail($id);

        if ($category->products_count > 0) {
            return $this->errorResponse("Kategori tidak bisa dihapus karena masih memiliki {$category->products_count} produk", null, 422);
        }

        $category->delete();

        return $this->successResponse(null, 'Kategori berhasil dihapus');
    }
}
