<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Warung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
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
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'OWNER',
            'is_active' => true,
        ]);
    }

    public function test_user_can_get_own_profile()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/users/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.email', 'test@example.com');
    }

    public function test_user_can_update_own_profile()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/users/me', [
                'name' => 'Updated Name',
                'phone' => '0899999999',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_owner_can_list_warung_users()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        User::create([
            'warung_id' => $this->warung->id,
            'name' => 'Staff 1',
            'email' => 'staff1@example.com',
            'password' => Hash::make('password'),
            'role' => 'KASIR',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/users');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_owner_can_create_staff()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/users', [
                'name' => 'New Staff',
                'email' => 'newstaff@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'KASIR',
                'phone' => '0812345678',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'newstaff@example.com',
            'warung_id' => $this->warung->id,
        ]);
    }

    public function test_owner_can_delete_staff()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $staff = User::create([
            'warung_id' => $this->warung->id,
            'name' => 'Staff to Delete',
            'email' => 'delete@example.com',
            'password' => Hash::make('password'),
            'role' => 'KASIR',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/v1/users/' . $staff->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $staff->id]);
    }

    public function test_owner_cannot_delete_self()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/v1/users/' . $this->user->id);

        $response->assertStatus(400);
        $this->assertDatabaseHas('users', ['id' => $this->user->id]);
    }
}
