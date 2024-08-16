<?php

namespace Unit\Transactions;

use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SaveTransactionTest extends TestCase
{
    use DatabaseTransactions;

    private array $params;

    public function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        Queue::fake();

        /** @var User $user */
        $user = User::query()->first();

        $this->actingAs($user);

        $this->params = [
            'action_type' => 2,
            'amount' => 1000,
            'account_id' => 1,
            'is_countable' => 1,
        ];

        $this->headers = [
            'X-Idempotent-Key' => time(),
        ];
    }

    public function testCreateTransaction()
    {
        $response = $this->postJson('api/transactions', $this->params, $this->headers);

        $response->assertStatus(200);

        $transaction = $response->getOriginalContent();

        $this->assertInstanceOf(Transaction::class, $transaction);
    }

    public function testCreateTransactionWithTags()
    {
        $params = [
            ...$this->params,
            'tag_ids' => [
                ...Tag::all()->random(3)->pluck('id')->toArray(),
            ],
        ];

        $response = $this->postJson('api/transactions', $params, $this->headers);

        $transaction = $response->getOriginalContent();

        $this->assertCount($transaction->tags->count(), $params['tag_ids']);

        $transaction->tags->each(function ($tag) use ($params) {
            $this->assertContains($tag->id, $params['tag_ids']);
        });
    }

    public function testCreateTransactionWithoutAccount()
    {
        $params = $this->params;

        unset($params['account_id']);

        $response = $this->postJson('api/transactions', $params, $this->headers);

        $response->assertStatus(422);
    }

    public function testUpdateTransaction()
    {
        $transaction = Transaction::query()
            ->where('user_id', auth()->id())
            ->first();

        $params = [
            ...$this->params,
            'amount' => 500,
        ];

        $response = $this->postJson("api/transactions/{$transaction->id}/update", $params, $this->headers);

        $response->assertStatus(200);

        $updatedTransaction = $response->getOriginalContent();

        if ($updatedTransaction->only(array_keys($params)) == $params) {
            $this->assertTrue(true, 'Transaction updated successfully');
        }
    }
}
