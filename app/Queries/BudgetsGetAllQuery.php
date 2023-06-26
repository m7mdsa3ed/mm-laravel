<?php

namespace App\Queries;

use App\Models\Budget;
use Illuminate\Support\Facades\DB;

class BudgetsGetAllQuery
{
    public static function get(int $currencyId): mixed
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
            ), 0) as balance
        ';

        return Budget::query()
            ->leftJoin('transactions', fn ($join) => $join
                ->on('transactions.category_id', '=', 'budgets.category_id')
                ->whereRaw($budgetTypeCaseRaw))
            ->leftJoin('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->leftJoin('currency_rates', function ($join) use ($currencyId) {
                $join->on('accounts.currency_id', '=', 'currency_rates.from_currency_id')
                    ->where('currency_rates.to_currency_id', '=', $currencyId);
            })
            ->groupBy('budgets.id')
            ->select('budgets.*', DB::raw($balanceRaw))
            ->with('category', 'user')
            ->get();
    }
}
