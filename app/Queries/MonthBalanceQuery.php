<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;

class MonthBalanceQuery
{
    public static function get(int $userId, int $currencyId): object
    {
        $subQuery = DB::table('transactions')
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->leftJoin('currency_rates', function ($join) use ($currencyId) {
                $join->on('accounts.currency_id', '=', 'currency_rates.from_currency_id')
                    ->where('currency_rates.to_currency_id', '=', $currencyId);
            })
            ->select(
                DB::raw('COALESCE(SUM(CASE WHEN action = 1 THEN amount ELSE 0 END), 0) AS in_amount'),
                DB::raw('COALESCE(SUM(CASE WHEN action = 2 THEN amount ELSE 0 END), 0) AS out_amount'),
                'currency_rates.rate'
            )
            ->whereNotIn('action_type', [3, 4])
            ->whereMonth('transactions.created_at', '=', DB::raw('EXTRACT(MONTH FROM CURRENT_DATE)'))
            ->whereYear('transactions.created_at', '=', DB::raw('EXTRACT(YEAR FROM CURRENT_DATE)'))
            ->where('transactions.user_id', '=', $userId)
            ->groupBy('accounts.currency_id', 'currency_rates.rate');

        return DB::query()->fromSub($subQuery, 'sub')
            ->select(
                DB::raw('SUM(in_amount * COALESCE(rate, 1)) AS in_amount'),
                DB::raw('SUM(out_amount * COALESCE(rate, 1)) AS out_amount')
            )
            ->first();
    }
}
