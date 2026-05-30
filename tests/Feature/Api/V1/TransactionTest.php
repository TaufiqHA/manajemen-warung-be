<?php

namespace Tests\Feature\Api\V1;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Warung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $warung;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warung = Warung::create([
            'name' => 'Warung Test',
            'is_tax_enabled' => true,
            'tax_percentage' => 10,
        ]);
        $this->user = User::create([
            'warung_id' => $this->warung->id,
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'role' => 'OWNER',
            'is_active' => true,
        ]);
        $this->product = Product::create([
            'warung_id' => $this->warung->id,
            'name' => 'Kopi',
            'price' => 5000,
            'stock' => 100,
            'is_active' => true,
        ]);
    }

    public function test_user_can_checkout_transaction()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/transactions', [
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                    ],
                ],
                'payment_method' => 'CASH',
                'paid_amount' => 20000,
                'discount_amount' => 0,
            ]);

        // Total: 2 * 5000 = 10000
        // Tax: 10% of 10000 = 1000
        // Grand Total: 11000
        // Change: 20000 - 11000 = 9000

        $response->assertStatus(201)
            ->assertJsonPath('data.grand_total', 11000)
            ->assertJsonPath('data.change_amount', 9000);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'stock' => 98,
        ]);
    }

    public function test_user_cannot_checkout_with_insufficient_stock()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/transactions', [
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 200,
                    ],
                ],
                'payment_method' => 'CASH',
                'paid_amount' => 1000000,
            ]);

        $response->assertStatus(400);
    }

    public function test_user_can_list_transactions()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $transaction = Transaction::create([
            'warung_id' => $this->warung->id,
            'cashier_id' => $this->user->id,
            'transaction_code' => 'TRX-1',
            'total_amount' => 10000,
            'grand_total' => 11000,
            'payment_method' => 'CASH',
            'paid_amount' => 20000,
            'change_amount' => 9000,
            'status' => 'COMPLETED',
        ]);

        $transaction->items()->create([
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'unit_price' => $this->product->price,
            'quantity' => 1,
            'subtotal' => $this->product->price,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/transactions');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_owner_can_cancel_transaction()
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $transaction = Transaction::create([
            'warung_id' => $this->warung->id,
            'cashier_id' => $this->user->id,
            'transaction_code' => 'TRX-1',
            'total_amount' => 5000,
            'grand_total' => 5500,
            'payment_method' => 'CASH',
            'paid_amount' => 10000,
            'change_amount' => 4500,
            'status' => 'COMPLETED',
        ]);

        $transaction->items()->create([
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'unit_price' => 5000,
            'quantity' => 1,
            'subtotal' => 5000,
        ]);

        // Current stock: 100. We didn't decrement it manually here, but cancel should increment it.
        // Wait, for a realistic test, we should starting from 99 if it was a real transaction.
        $this->product->decrement('stock', 1);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/v1/transactions/' . $transaction->id . '/cancel', [
                'reason' => 'Salah input',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'CANCELLED',
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'stock' => 100,
        ]);
    }
}
