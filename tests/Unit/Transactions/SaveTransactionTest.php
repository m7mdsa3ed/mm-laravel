<?php

namespace Unit\Transactions;

use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SaveTransactionTest extends TestCase
{
    use DatabaseTransactions;

    private array $params;

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

    public function testBasicSave()
    {
        $response = $this->postJson('api/transactions', $this->params);

        $response->assertStatus(201);

        $transaction = $response->getOriginalContent();

        $this->assertInstanceOf(Transaction::class, $transaction);
    }

    public function testSaveWithTags()
    {
        $params = [
            ...$this->params,
            'tag_ids' => [
                ...Tag::all()->random(3)->pluck('id')->toArray(),
            ],
        ];

        $response = $this->postJson('api/transactions', $params);

        $transaction = $response->getOriginalContent();

        $this->assertCount($transaction->tags->count(), $params['tag_ids']);

        $transaction->tags->each(function ($tag) use ($params) {
            $this->assertContains($tag->id, $params['tag_ids']);
        });
    }

    public function testSaveWithoutAccount()
    {
        $params = $this->params;

        unset($params['account_id']);

        $response = $this->postJson('api/transactions', $params);

        $response->assertStatus(422);
    }
}
