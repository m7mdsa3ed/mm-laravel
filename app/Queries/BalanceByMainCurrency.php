<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;

class BalanceByMainCurrency
{
    public static function get(int $userId, int $toCurrencyId): object
    {
        $subQuery = DB::table('transactions')
            ->join('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->join('currencies', 'currencies.id', '=', 'accounts.currency_id')
            ->leftJoin('currency_rates', function ($join) use ($toCurrencyId) {
                $join->on('currencies.id', '=', 'currency_rates.from_currency_id')
                    ->where('currency_rates.to_currency_id', '=', $toCurrencyId);
            })
            ->leftJoin('user_currency_rates', function ($join) use ($userId) {
                $join->on('currency_rates.id', '=', 'user_currency_rates.currency_rate_id')
                    ->where('user_currency_rates.user_id', '=', $userId);
            })
            ->select(
                DB::raw('SUM(CASE WHEN action = 1 THEN amount ELSE -amount END) AS amount'),
                DB::raw('SUM(CASE WHEN action_type IN (4) THEN CASE WHEN action = 1 THEN amount ELSE -amount END ELSE 0 END) * -1 AS loan_amount'),
                DB::raw('SUM(CASE WHEN action_type IN (5) THEN CASE WHEN action = 1 THEN amount ELSE -amount END ELSE 0 END) * -1 AS debit_amount'),
                DB::raw('SUM(CASE WHEN action_type IN (6) THEN CASE WHEN action = 1 THEN amount ELSE -amount END ELSE 0 END) * -1 AS held_amount'),
                DB::raw('COALESCE(MIN(COALESCE(user_currency_rates.rate, currency_rates.rate)), 1) AS currency_rate')
            )
            ->where('transactions.user_id', '=', $userId)
            ->groupBy('currencies.id');

        return DB::query()->fromSub($subQuery, 'sub')
            ->select(
                DB::raw('SUM(amount * currency_rate) AS amount'),
                DB::raw('SUM(loan_amount * currency_rate) AS loan_amount'),
                DB::raw('SUM(debit_amount * currency_rate) AS debit_amount')
            )
            ->first();
    }
}
