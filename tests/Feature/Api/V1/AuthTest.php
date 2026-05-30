<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Warung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_warung()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '08123456789',
            'warung_name' => 'Warung Berkah',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'warung_id',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('warungs', ['name' => 'Warung Berkah']);
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'OWNER',
        ]);
    }

    public function test_user_can_login()
    {
        $warung = Warung::create(['name' => 'Warung Test']);
        $user = User::create([
            'warung_id' => $warung->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'OWNER',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'token_type',
                    'user',
                ],
            ]);
    }

    public function test_user_cannot_login_with_wrong_credentials()
    {
        $warung = Warung::create(['name' => 'Warung Test']);
        User::create([
            'warung_id' => $warung->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'OWNER',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_logout()
    {
        $warung = Warung::create(['name' => 'Warung Test']);
        $user = User::create([
            'warung_id' => $warung->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'OWNER',
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
        $this->assertCount(0, $user->tokens);
    }

    public function test_user_can_change_password()
    {
        $warung = Warung::create(['name' => 'Warung Test']);
        $user = User::create([
            'warung_id' => $warung->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'OWNER',
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/auth/change-password', [
                'current_password' => 'password',
                'new_password' => 'new_password',
                'new_password_confirmation' => 'new_password',
            ]);

        $response->assertStatus(200);
        $this->assertTrue(Hash::check('new_password', $user->fresh()->password));
    }
}
