<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Transaction;
use App\Enums\AccountType;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_user_transactions_with_balance()
    {
        $user = User::factory()->create();

        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 100,
            'fee' => 10
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api');

        $response->assertStatus(200)
            ->assertJson([
                'user_id' => $user->id,
                'name' => $user->name,
                'account_type' => $user->account_type,
                'balance' => 90,
                'transactions' => [
                    [
                        'id' => $transaction->id,
                        'user_id' => $user->id,
                        'amount' => 100,
                        'fee' => 10,
                    ]
                ]
            ]);
    }


    public function test_deposit()
    {
        $user = User::factory()->create();
        $initialBalance = $user->balance;

        $response = $this->actingAs($user)
            ->postJson('/api/deposit', ['amount' => 100]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertEquals($initialBalance + 100, $user->balance);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'transaction_type' => 'deposit',
            'amount' => 100,
            'fee' => 0,
        ]);
    }


    public function test_withdrawal_free_if_under_1000()
    {
        $user = User::factory()->create(['balance' => 1000]);
        $initialBalance = $user->balance;

        $response = $this->actingAs($user)
            ->postJson('/api/withdrawal', ['amount' => 200]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertEquals($initialBalance - 200, $user->balance);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'transaction_type' => 'withdrawal',
            'amount' => 200,
            'fee' => 0, // Fee for individual account based on the provided logic
        ]);
    }

    public function test_withdrawal_1000_5000()
    {
        $user = User::factory()->create(['balance' => 10000]); // Assuming initial balance is $10,000


        $response = $this->actingAs($user)
            ->postJson('/api/withdrawal', ['amount' => 7000]);

        $response->assertStatus(200);

        $user->refresh();

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'transaction_type' => 'withdrawal',
            'amount' => 7000.00,
            'fee' => 15.00, // Fee for individual account based on the provided logic
        ]);
    }

    public function test_withdrawal_business()
    {
        $user = User::factory()->create([
            'balance' => 60000, // Initial balance of $60,000
            'account_type' => AccountType::Business()->value, // Set the user's account type to Business
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/withdrawal', ['amount' => 50000]);

        $response->assertStatus(200);

        $response = $this->actingAs($user)
            ->postJson('/api/withdrawal', ['amount' => 1000]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'transaction_type' => 'withdrawal',
            'amount' => 1000.00,
            'fee' => 15.00,
        ]);
    }
}
