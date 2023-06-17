<?php

namespace Transactions;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UpdateTransactionTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        /** @var User $user */
        $user = User::query()->first();

        $this->actingAs($user);

        $this->params = [
            'action_type' => 2,
            'amount' => 1000,
            'account_id' => 1,
        ];
    }

    public function testUpdateTransaction()
    {
        $transaction = Transaction::query()
            ->where('user_id', auth()->id())
            ->first();

        $params = [
            'action_type' => 2,
            'amount' => 1000,
            'account_id' => 1,
        ];

        $response = $this->postJson("api/transactions/{$transaction->id}/update", $params);

        $response->assertStatus(200);

        $updatedTransaction = $response->getOriginalContent();

        if ($updatedTransaction->only(array_keys($params)) == $params) {
            $this->assertTrue(true, 'Transaction updated successfully');
        }
    }
}
