<?php

namespace App\Queries;

use App\Enums\ActionEnum;
use App\Enums\ActionTypeEnum;
use App\Models\Budget;
use Illuminate\Support\Facades\DB;

class BudgetsGetAllQuery
{
    public static function get(int $userId, int $currencyId, array $budgetIds = []): mixed
    {
        $budgetTypeCaseRaw = '
            CASE
                WHEN budgets.type = 1
                    THEN EXTRACT(MONTH FROM transactions.created_at) = EXTRACT(MONTH FROM CURRENT_DATE)
                        AND EXTRACT(YEAR FROM transactions.created_at) = EXTRACT(YEAR FROM CURRENT_DATE)
                WHEN budgets.type = 2
                    THEN EXTRACT(YEAR FROM transactions.created_at) = EXTRACT(YEAR FROM CURRENT_DATE)
                ELSE false
            END
        ';

        $balanceRaw = '
            COALESCE(SUM(
                CASE
                    WHEN transactions.action = 1
                        THEN - transactions.amount
                    ELSE transactions.amount
                END
                *
                COALESCE(currency_rates.rate, 1)
            ), 0) as balance,

             COALESCE(SUM(
                CASE
                    WHEN transactions.action = 1
                        THEN - transactions.amount
                    ELSE transactions.amount
                END
                *
                COALESCE(currency_rates.rate, 1)
            ), 0) / budgets.amount * 100 as percentage
        ';

        return Budget::query()
            ->leftJoin('budget_categories', 'budgets.id', '=', 'budget_categories.budget_id')
            ->leftJoin('transactions', fn ($join) => $join
                ->on('transactions.category_id', '=', 'budget_categories.category_id')
                ->whereRaw($budgetTypeCaseRaw))
            ->where('transactions.action', ActionEnum::OUT->value)
            ->whereIn('transactions.action_type', [
                ActionTypeEnum::INCOME->value,
                ActionTypeEnum::OUTCOME->value,
            ])
            ->leftJoin('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->leftJoin('currency_rates', function ($join) use ($currencyId) {
                $join->on('accounts.currency_id', '=', 'currency_rates.from_currency_id')
                    ->where('currency_rates.to_currency_id', '=', $currencyId);
            })
            ->groupBy('budgets.id')
            ->select('budgets.*', DB::raw($balanceRaw))
            ->with('categories')
            ->when(count($budgetIds), fn ($query) => $query->whereIn('budgets.id', $budgetIds))
            ->where('budgets.user_id', $userId)
            ->get();
    }
}
