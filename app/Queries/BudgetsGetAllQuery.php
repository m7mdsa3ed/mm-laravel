<?php

namespace App\Queries;

use App\Enums\ActionEnum;
use App\Enums\ActionTypeEnum;
use App\Models\Budget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BudgetsGetAllQuery
{
    public static function get(
        int $userId,
        int $currencyId,
        array $budgetIds = [],
        array $categoryIds = [],
        bool $exceededOnly = false
    ): Collection {
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
                COALESCE(COALESCE(user_currency_rates.rate, currency_rates.rate), 1)
            ), 0) as balance,

             COALESCE(SUM(
                CASE
                    WHEN transactions.action = 1
                        THEN - transactions.amount
                    ELSE transactions.amount
                END
                *
                COALESCE(COALESCE(user_currency_rates.rate, currency_rates.rate), 1)
            ), 0) / budgets.amount * 100 as percentage
        ';

        return Budget::query()
            ->leftJoin('budget_categories', 'budgets.id', '=', 'budget_categories.budget_id')
            ->leftJoin('transactions', fn ($join) => $join
                ->on('transactions.category_id', '=', 'budget_categories.category_id')
                ->whereRaw($budgetTypeCaseRaw))
            ->where(
                fn ($query) => $query
                    ->where(
                        fn ($q) => $q->where('transactions.action', ActionEnum::OUT->value)
                            ->whereIn('transactions.action_type', [
                                ActionTypeEnum::INCOME->value,
                                ActionTypeEnum::OUTCOME->value,
                            ])
                    )
                    ->orWhereNull('transactions.id')
            )
            ->leftJoin('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->leftJoin('currency_rates', function ($join) use ($currencyId) {
                $join->on('accounts.currency_id', '=', 'currency_rates.from_currency_id')
                    ->where('currency_rates.to_currency_id', '=', $currencyId);
            })
            ->leftJoin('user_currency_rates', function ($join) use ($userId) {
                $join->on('currency_rates.id', '=', 'user_currency_rates.currency_rate_id')
                    ->where('user_currency_rates.user_id', '=', $userId);
            })
            ->groupBy('budgets.id')
            ->select('budgets.*', DB::raw($balanceRaw))
            ->with('categories')
            ->when(count($budgetIds), fn ($query) => $query->whereIn('budgets.id', $budgetIds))
            ->when(count($categoryIds), fn ($query) => $query->whereIn('budget_categories.category_id', $categoryIds))
            ->when($exceededOnly, fn ($query) => $query->havingRaw('percentage >= budgets.notify_percentage'))
            ->where('budgets.user_id', $userId)
            ->get();
    }
}
