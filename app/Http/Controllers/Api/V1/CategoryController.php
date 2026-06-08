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
            ->orderBy('order', 'asc')
            ->orderBy('created_at', 'desc')
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

    public function updateLayout(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'categories' => 'required|array',
            'categories.*.name' => 'required|string',
            'categories.*.order' => 'required|integer',
        ]);

        foreach ($request->categories as $cat) {
            Category::where('warung_id', $user->warung_id)
                ->where('name', $cat['name'])
                ->update(['order' => $cat['order']]);
        }

        return $this->successResponse(null, 'Tata letak kategori berhasil disimpan');
    }
}
