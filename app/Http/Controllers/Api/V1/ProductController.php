<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductImageRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\UpdateProductStockRequest;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();

        $query = Product::with('category')->where('warung_id', $user->warung_id);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSorts = ['name', 'price', 'stock', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        $products = $query->get();

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        if (is_string($id) && str_starts_with($id, 'PRD-')) {
            $id = (int) substr($id, 4);
        }

        $product = Product::with('category')->where('warung_id', $user->warung_id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $user = $request->user();

        $categoryId = null;
        if ($request->has('category')) {
            $category = Category::firstOrCreate([
                'warung_id' => $user->warung_id,
                'name' => $request->input('category'),
            ]);
            $categoryId = $category->id;
        } elseif ($request->has('category_id')) {
            $categoryId = $request->category_id;
        }

        $imageUrl = $request->input('imageUrl') ?? $request->input('image_url');

        $product = Product::create([
            'warung_id' => $user->warung_id,
            'category_id' => $categoryId,
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,
            'unit' => $request->unit ?? 'pcs',
            'image_url' => $imageUrl,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Barang berhasil ditambahkan',
            'data' => new ProductResource($product),
        ], 201);
    }

    public function update(UpdateProductRequest $request, $id)
    {
        $user = $request->user();

        if (is_string($id) && str_starts_with($id, 'PRD-')) {
            $id = (int) substr($id, 4);
        }

        $product = Product::where('warung_id', $user->warung_id)->findOrFail($id);

        $dataToUpdate = $request->only('name', 'description', 'price', 'stock', 'unit', 'is_active');

        if ($request->has('category')) {
            $category = Category::firstOrCreate([
                'warung_id' => $user->warung_id,
                'name' => $request->input('category'),
            ]);
            $dataToUpdate['category_id'] = $category->id;
        } elseif ($request->has('category_id')) {
            $dataToUpdate['category_id'] = $request->category_id;
        }

        $imageUrl = $request->input('imageUrl') ?? $request->input('image_url');
        if ($imageUrl !== null) {
            $dataToUpdate['image_url'] = $imageUrl;
        }

        $product->update($dataToUpdate);

        return response()->json([
            'success' => true,
            'message' => 'Barang berhasil diperbarui',
            'data' => new ProductResource($product),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        if (is_string($id) && str_starts_with($id, 'PRD-')) {
            $id = (int) substr($id, 4);
        }

        $product = Product::where('warung_id', $user->warung_id)->findOrFail($id);

        if ($product->image_url) {
            Storage::disk('public')->delete(str_replace('storage/', '', $product->image_url));
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Barang berhasil dihapus',
        ]);
    }

    public function uploadImage(ProductImageRequest $request, $id)
    {
        $user = $request->user();

        if (is_string($id) && str_starts_with($id, 'PRD-')) {
            $id = (int) substr($id, 4);
        }

        $product = Product::where('warung_id', $user->warung_id)->findOrFail($id);

        if ($product->image_url) {
            Storage::disk('public')->delete(str_replace('storage/', '', $product->image_url));
        }

        $path = $request->file('image')->store('products', 'public');
        $product->update(['image_url' => 'storage/'.$path]);

        return $this->successResponse(['image_url' => url('storage/'.$path)], 'Gambar produk berhasil diunggah');
    }

    public function updateStock(UpdateProductStockRequest $request, $id)
    {
        $user = $request->user();

        if (is_string($id) && str_starts_with($id, 'PRD-')) {
            $id = (int) substr($id, 4);
        }

        $product = Product::where('warung_id', $user->warung_id)->findOrFail($id);
        $product->update(['stock' => $request->stock]);

        return $this->successResponse(new ProductResource($product), 'Stok produk berhasil diperbarui');
    }

    /**
     * Ekspor daftar produk ke file CSV yang kompatibel dengan Excel.
     */
    public function export(Request $request)
    {
        $user = $request->user();

        // 1. Inisialisasi query dengan filter yang sama dengan method index()
        $query = Product::with('category')->where('warung_id', $user->warung_id);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSorts = ['name', 'price', 'stock', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        $products = $query->get();

        // 2. Tentukan path file di folder public/exports
        $fileName = 'daftar_produk_'.date('Y-m-d_H-i-s').'.csv';
        $directory = public_path('exports');

        // Buat folder jika belum ada
        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $filePath = $directory.'/'.$fileName;

        // 3. Tulis data ke dalam file secara fisik
        $fileHandle = fopen($filePath, 'w');
        fprintf($fileHandle, chr(0xEF).chr(0xBB).chr(0xBF)); // Tulis BOM UTF-8

        fputcsv($fileHandle, [
            'ID Produk',
            'Nama Produk',
            'Kategori',
            'Deskripsi',
            'Harga',
            'Stok',
            'Satuan',
            'Status',
            'Tanggal Dibuat',
        ]);

        foreach ($products as $product) {
            fputcsv($fileHandle, [
                'PRD-'.$product->id,
                $product->name,
                $product->category ? $product->category->name : '-',
                $product->description ?? '-',
                (int) $product->price,
                (int) $product->stock,
                $product->unit,
                $product->is_active ? 'Aktif' : 'Tidak Aktif',
                $product->created_at->format('Y-m-d H:i:s'),
            ]);
        }
        fclose($fileHandle);

        // Pastikan file dapat dibaca oleh publik/web server
        chmod($filePath, 0644);

        // 4. Kembalikan URL publik menggunakan JSON Response
        return response()->json([
            'success' => true,
            'message' => 'File berhasil dibuat',
            'download_url' => asset('exports/'.$fileName),
        ]);
    }
}
