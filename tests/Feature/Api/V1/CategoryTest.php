<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Warung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $warung;

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
    }

    public function test_user_can_list_categories()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        Category::create([
            'warung_id' => $this->warung->id,
            'name' => 'Makanan',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_owner_can_create_category()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/categories', [
                'name' => 'Minuman',
                'description' => 'Segala jenis minuman',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('categories', [
            'name' => 'Minuman',
            'warung_id' => $this->warung->id,
        ]);
    }

    public function test_owner_can_update_category()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $category = Category::create([
            'warung_id' => $this->warung->id,
            'name' => 'Lama',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/categories/' . $category->id, [
                'name' => 'Baru',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Baru',
        ]);
    }

    public function test_owner_cannot_delete_category_with_products()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $category = Category::create([
            'warung_id' => $this->warung->id,
            'name' => 'Kategori Ada Produk',
        ]);

        Product::create([
            'warung_id' => $this->warung->id,
            'category_id' => $category->id,
            'name' => 'Produk 1',
            'price' => 1000,
            'stock' => 10,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/v1/categories/' . $category->id);

        $response->assertStatus(422);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_owner_can_delete_empty_category()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $category = Category::create([
            'warung_id' => $this->warung->id,
            'name' => 'Kategori Kosong',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/v1/categories/' . $category->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
