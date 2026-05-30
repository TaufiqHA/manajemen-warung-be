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

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_owner_can_create_product()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
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

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/products/' . $product->id, [
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

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/v1/products/' . $product->id . '/stock', [
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

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/products/' . $product->id . '/image', [
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

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/v1/products/' . $product->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
