<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;

class CurrentMonthComparedToLastYear
{
    public static function get(int $userId, int $currencyId): array
    {
        return DB::table('transactions')
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->leftJoin('currency_rates', function ($join) use ($currencyId) {
                $join->on('accounts.currency_id', '=', 'currency_rates.from_currency_id')
                    ->where('currency_rates.to_currency_id', '=', $currencyId);
            })
            ->select(
                DB::raw('SUM(CASE WHEN action = 1 THEN amount ELSE -amount END * COALESCE(currency_rates.rate, 1)) AS amount'),
                DB::raw('EXTRACT(MONTH FROM transactions.created_at) AS month'),
                DB::raw('EXTRACT(YEAR FROM transactions.created_at) AS year')
            )
            ->where(function ($query) {
                $query->whereMonth('transactions.created_at', DB::raw('EXTRACT(MONTH FROM CURRENT_DATE)'))
                    ->orWhereMonth('transactions.created_at', DB::raw('EXTRACT(MONTH FROM CURRENT_DATE - INTERVAL 1 YEAR)'));
            })
            ->where(function ($query) {
                $query->whereYear('transactions.created_at', DB::raw('EXTRACT(YEAR FROM CURRENT_DATE)'))
                    ->orWhereYear('transactions.created_at', DB::raw('EXTRACT(YEAR FROM CURRENT_DATE - INTERVAL 1 YEAR)'));
            })
            ->where('transactions.action', '=', 2)
            ->where('action_type', '!=', 3)
            ->where('transactions.user_id', '=', $userId)
            ->groupBy(DB::raw('EXTRACT(MONTH FROM transactions.created_at)'), DB::raw('EXTRACT(YEAR FROM transactions.created_at)'))
            ->get()
            ->toArray();
    }
}
