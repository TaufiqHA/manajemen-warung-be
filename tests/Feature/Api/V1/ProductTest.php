<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Warung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $warung;

    protected $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warung = Warung::create(['name' => 'Warung Test']);
        $this->user = User::create([
            'warung_id' => $this->warung->id,
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'role' => 'OWNER',
            'is_active' => true,
        ]);
        $this->category = Category::create([
            'warung_id' => $this->warung->id,
            'name' => 'Makanan',
        ]);
    }

    public function test_user_can_list_products()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        Product::create([
            'warung_id' => $this->warung->id,
            'category_id' => $this->category->id,
            'name' => 'Nasi Goreng',
            'price' => 15000,
            'stock' => 50,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_owner_can_create_product()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/products', [
                'category_id' => $this->category->id,
                'name' => 'Mie Goreng',
                'price' => 12000,
                'stock' => 100,
                'unit' => 'porsi',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('products', [
            'name' => 'Mie Goreng',
            'warung_id' => $this->warung->id,
        ]);
    }

    public function test_owner_can_update_product()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $product = Product::create([
            'warung_id' => $this->warung->id,
            'name' => 'Lama',
            'price' => 1000,
            'stock' => 10,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/products/'.$product->id, [
                'name' => 'Baru',
                'price' => 2000,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Baru',
            'price' => 2000,
        ]);
    }

    public function test_owner_can_update_product_stock()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $product = Product::create([
            'warung_id' => $this->warung->id,
            'name' => 'Produk',
            'price' => 1000,
            'stock' => 10,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/products/'.$product->id.'/stock', [
                'stock' => 20,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 20,
        ]);
    }

    public function test_owner_can_upload_product_image()
    {
        Storage::fake('public');
        $token = $this->user->createToken('test_token')->plainTextToken;

        $product = Product::create([
            'warung_id' => $this->warung->id,
            'name' => 'Produk',
            'price' => 1000,
            'stock' => 10,
        ]);

        $file = UploadedFile::fake()->image('product.jpg');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/products/'.$product->id.'/image', [
                'image' => $file,
            ]);

        $response->assertStatus(200);
        $product->refresh();
        $this->assertNotNull($product->image_url);
        Storage::disk('public')->assertExists(str_replace('storage/', '', $product->image_url));
    }

    public function test_owner_can_delete_product()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $product = Product::create([
            'warung_id' => $this->warung->id,
            'name' => 'Hapus',
            'price' => 1000,
            'stock' => 10,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/products/'.$product->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_user_can_export_products_to_csv()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        // Buat dummy produk
        Product::create([
            'warung_id' => $this->warung->id,
            'category_id' => $this->category->id,
            'name' => 'Nasi Goreng Spesial',
            'price' => 15000,
            'stock' => 50,
            'unit' => 'porsi',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/products/export');

        // Verifikasi response status dan format JSON
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'download_url',
        ]);

        $url = $response->json('download_url');
        $this->assertNotNull($url);

        // Ambil nama file dari URL
        $fileName = basename($url);
        $filePath = public_path('exports/'.$fileName);

        // Pastikan file tersebut benar-benar dibuat
        $this->assertFileExists($filePath);

        // Verifikasi konten file
        $content = file_get_contents($filePath);
        $this->assertStringContainsString('ID Produk', $content);
        $this->assertStringContainsString('Nama Produk', $content);
        $this->assertStringContainsString('Kategori', $content);
        $this->assertStringContainsString('Nasi Goreng Spesial', $content);
        $this->assertStringContainsString('15000', $content);

        // Bersihkan file setelah pengujian selesai
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    public function test_user_can_export_products_to_csv_using_query_token()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        // Buat dummy produk
        Product::create([
            'warung_id' => $this->warung->id,
            'category_id' => $this->category->id,
            'name' => 'Nasi Goreng Spesial',
            'price' => 15000,
            'stock' => 50,
            'unit' => 'porsi',
            'is_active' => true,
        ]);

        // Hit route tanpa header Authorization, tapi dengan query ?token=...
        $response = $this->getJson('/api/v1/products/export?token='.$token);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'download_url',
        ]);

        $url = $response->json('download_url');
        $fileName = basename($url);
        $filePath = public_path('exports/'.$fileName);

        // Bersihkan file
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    public function test_kasir_user_can_export_products_to_csv()
    {
        $kasir = User::create([
            'warung_id' => $this->warung->id,
            'name' => 'Kasir',
            'email' => 'kasir@example.com',
            'password' => Hash::make('password'),
            'role' => 'KASIR',
            'is_active' => true,
        ]);

        $token = $kasir->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/products/export');

        $response->assertStatus(200);

        // Bersihkan file jika dibuat
        if ($response->status() === 200) {
            $url = $response->json('download_url');
            $fileName = basename($url);
            $filePath = public_path('exports/'.$fileName);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
}
