<?php

namespace App\Listeners;

use App\Enums\ActionEnum;
use App\Jobs\BudgetNotificationJob;
use App\Models\Budget;
use App\Queries\BudgetsGetAllQuery;

class CheckBudgetBalanceAfterTransactionSaved
{
    public function __construct()
    {
    }

    public function handle(object $event): void
    {
        /** @var \App\Models\Transaction $transaction */
        $transaction = $event->transaction;

        if ($transaction->action === ActionEnum::IN->value) {
            return;
        }

        $continue = $this->checkChanges($event->changes ?? []);

        if (!$continue) {
            return;
        }

        $user = $transaction->user;

        $mainCurrency = $user->getMainCurrency();

        $budgetsAlmostExceeded = $this->getBudgetsAlmostExceeded(
            $user->id,
            $mainCurrency->id
        );

        foreach ($budgetsAlmostExceeded as $budget) {
            $message = $this->getNotificationMessage($budget);

            dispatch(new BudgetNotificationJob($user, $message));
        }
    }

    private function getBudgetsAlmostExceeded(int $userId, int $currencyId)
    {
        $budgets = BudgetsGetAllQuery::get(
            userId: $userId,
            currencyId: $currencyId,
        );

        return $budgets->where('percentage', '>=', 80);
    }

    private function getNotificationMessage(Budget $budget): string
    {
        $budgetName = $budget->name;

        $budgetAmount = $budget->amount;

        $budgetPercentage = $budget->percentage;

        return "Your budget $budgetName is almost exceeded. You have spent $budgetPercentage% of $budgetAmount.";
    }

    private function checkChanges(array $changes): bool
    {
        if (!count($changes)) {
            return false;
        }

        $listeners = [
            'amount',
            'category_id',
        ];

        return count(array_intersect($listeners, array_keys($changes)));
    }
}
