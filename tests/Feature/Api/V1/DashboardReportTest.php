<?php

namespace Tests\Feature\Api\V1;

use App\Models\Expense;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Warung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardReportTest extends TestCase
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

    public function test_user_can_get_dashboard_stats()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        Transaction::create([
            'warung_id' => $this->warung->id,
            'cashier_id' => $this->user->id,
            'transaction_code' => 'TRX-1',
            'total_amount' => 10000,
            'grand_total' => 10000,
            'payment_method' => 'CASH',
            'paid_amount' => 10000,
            'change_amount' => 0,
            'status' => 'COMPLETED',
            'created_at' => now(),
        ]);

        Expense::create([
            'warung_id' => $this->warung->id,
            'created_by' => $this->user->id,
            'title' => 'Listrik',
            'amount' => 2000,
            'category' => 'LISTRIK',
            'date' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('data.today.total_revenue', 10000)
            ->assertJsonPath('data.today.total_expenses', 2000)
            ->assertJsonPath('data.today.net_profit', 8000);
    }

    public function test_user_can_get_sales_report()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/reports/sales?period=DAILY');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['summary', 'chart_data', 'top_products']]);
    }

    public function test_user_can_get_profit_loss_report()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $dateFrom = now()->startOfMonth()->format('Y-m-d');
        $dateTo = now()->endOfMonth()->format('Y-m-d');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/reports/profit-loss?date_from={$dateFrom}&date_to={$dateTo}");

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['revenue', 'expenses', 'net_profit']]);
    }
}
