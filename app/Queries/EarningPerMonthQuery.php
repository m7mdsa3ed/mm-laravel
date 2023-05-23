<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;

class EarningPerMonthQuery
{
    public static function get(string $userId, int $currencyId, int $monthsCount = 12): array
    {
        return DB::table('transactions')
            ->join('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->leftJoin('currency_rates', function ($join) use ($currencyId) {
                $join->on('accounts.currency_id', '=', 'currency_rates.from_currency_id')
                    ->where('currency_rates.to_currency_id', '=', $currencyId);
            })
            ->select(
                DB::raw('(CAST(SUM(CASE WHEN action = 1 THEN amount ELSE - amount END * COALESCE(currency_rates.rate, 1)) AS DECIMAL(10, 4))) as amount'),
                DB::raw('EXTRACT(MONTH FROM transactions.created_at) AS month'),
                DB::raw('EXTRACT(YEAR FROM transactions.created_at) AS year')
            )
            ->whereDate('transactions.created_at', '>=', now()->subMonths($monthsCount))
            ->where('transactions.action', '=', 1)
            ->where('action_type', '<>', 3)
            ->where('transactions.user_id', '=', $userId)
            ->groupBy(
                DB::raw('EXTRACT(MONTH FROM transactions.created_at)'),
                DB::raw('EXTRACT(YEAR FROM transactions.created_at)')
            )
            ->get()
            ->toArray();
    }
}
