<?php

namespace Budgets;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SaveBudgetTest extends TestCase
{
    use DatabaseTransactions;

    private array $params;

    public function setUp(): void
    {
        parent::setUp();

        /** @var User $user */
        $user = User::query()->first();

        $this->actingAs($user);
    }

    public function testCreateBudget()
    {
        $this->assertTrue(true);
    }

    public function testUpdateBudget()
    {
        $this->assertTrue(true);
    }
}
