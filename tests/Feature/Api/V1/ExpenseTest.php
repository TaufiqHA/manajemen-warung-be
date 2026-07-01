<?php

namespace Tests\Feature\Api\V1;

use App\Models\Expense;
use App\Models\User;
use App\Models\Warung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExpenseTest extends TestCase
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

    public function test_user_can_list_expenses()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        Expense::create([
            'warung_id' => $this->warung->id,
            'created_by' => $this->user->id,
            'title' => 'Listrik',
            'amount' => 500000,
            'category' => 'LISTRIK',
            'date' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/expenses');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_owner_can_create_expense()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/expenses', [
                'title' => 'Air PDAM',
                'amount' => 100000,
                'category' => 'AIR',
                'date' => now()->format('Y-m-d'),
                'note' => 'Bulan Mei',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('expenses', [
            'title' => 'Air PDAM',
            'amount' => 100000,
            'warung_id' => $this->warung->id,
        ]);
    }

    public function test_owner_can_update_expense()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $expense = Expense::create([
            'warung_id' => $this->warung->id,
            'created_by' => $this->user->id,
            'title' => 'Lama',
            'amount' => 1000,
            'category' => 'LAINNYA',
            'date' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/expenses/'.$expense->id, [
                'title' => 'Baru',
                'amount' => 2000,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'title' => 'Baru',
            'amount' => 2000,
        ]);
    }

    public function test_owner_can_delete_expense()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $expense = Expense::create([
            'warung_id' => $this->warung->id,
            'created_by' => $this->user->id,
            'title' => 'Hapus',
            'amount' => 1000,
            'category' => 'LAINNYA',
            'date' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/expenses/'.$expense->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_create_expense_with_indonesian_date()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/expenses', [
                'title' => 'Sewa Kios',
                'amount' => 1500000,
                'category' => 'SEWA',
                'tanggal' => 'Rabu, 15 Juli 2026',
                'note' => 'Pembayaran Sewa',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('expenses', [
            'title' => 'Sewa Kios',
            'amount' => 1500000,
            'date' => '2026-07-15 00:00:00',
        ]);
    }

    public function test_update_expense_with_indonesian_date()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $expense = Expense::create([
            'warung_id' => $this->warung->id,
            'created_by' => $this->user->id,
            'title' => 'Air',
            'amount' => 50000,
            'category' => 'AIR',
            'date' => '2026-06-01',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/expenses/'.$expense->id, [
                'tanggal' => 'Kamis, 16 Juli 2026',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'date' => '2026-07-16 00:00:00',
        ]);
    }
}
