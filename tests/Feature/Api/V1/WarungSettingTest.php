<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Warung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WarungSettingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $warung;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warung = Warung::create([
            'name' => 'Original Warung',
            'address' => 'Old Address',
            'phone' => '08123',
        ]);
        $this->user = User::create([
            'warung_id' => $this->warung->id,
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'role' => 'OWNER',
            'is_active' => true,
        ]);
    }

    public function test_user_can_get_warung_settings()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/settings/warung');

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Original Warung');
    }

    public function test_owner_can_update_warung_settings()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/settings/warung', [
                'name' => 'Updated Warung',
                'address' => 'New Address',
                'phone' => '08456',
                'tax_percentage' => 10,
                'is_tax_enabled' => true,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('warungs', [
            'id' => $this->warung->id,
            'name' => 'Updated Warung',
            'address' => 'New Address',
        ]);
    }

    public function test_owner_can_upload_warung_logo()
    {
        Storage::fake('public');
        $token = $this->user->createToken('test_token')->plainTextToken;

        $file = UploadedFile::fake()->image('logo.jpg');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/settings/warung/logo', [
                'logo' => $file,
            ]);

        $response->assertStatus(200);
        $this->warung->refresh();
        $this->assertNotNull($this->warung->logo_url);
        Storage::disk('public')->assertExists(str_replace('storage/', '', $this->warung->logo_url));
    }

    public function test_non_owner_cannot_update_warung_settings()
    {
        $staff = User::create([
            'warung_id' => $this->warung->id,
            'name' => 'Staff',
            'email' => 'staff@example.com',
            'password' => Hash::make('password'),
            'role' => 'ADMIN_TOKO',
        ]);

        $token = $staff->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/settings/warung', [
                'name' => 'Hack Name',
            ]);

        $response->assertStatus(403);
    }
}
