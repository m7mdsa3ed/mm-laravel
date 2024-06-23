<?php

namespace App\Queries;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MonthBalanceQuery
{
    public static function get(int $userId, int $currencyId, Carbon $from, Carbon $to): mixed
    {
        return DB::table('transactions')
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->join('currencies', 'accounts.currency_id', '=', 'currencies.id')
            ->select(
                ...array_map(fn($sql) => DB::raw($sql), [
                    'COALESCE(SUM(CASE WHEN action = 1 AND transactions.is_countable = 1 THEN amount ELSE 0 END), 0) AS in_amount',
                    'COALESCE(SUM(CASE WHEN action = 1 AND transactions.is_countable = 0 THEN amount ELSE 0 END), 0) AS in_adjustment_amount',
                    'COALESCE(SUM(CASE WHEN action = 1 AND action_type = 4 THEN amount ELSE 0 END), 0) AS in_loan_amount',
                    'COALESCE(SUM(CASE WHEN action = 1 AND action_type = 5 THEN amount ELSE 0 END), 0) AS in_debit_amount',
                    'COALESCE(SUM(CASE WHEN action = 1 AND action_type = 6 THEN amount ELSE 0 END), 0) AS in_held_amount',
                    'COALESCE(SUM(CASE WHEN action = 2 AND transactions.is_countable = 1 THEN amount ELSE 0 END), 0) AS out_amount',
                    'COALESCE(SUM(CASE WHEN action = 2 AND transactions.is_countable = 0 THEN amount ELSE 0 END), 0) AS out_adjustment_amount',
                    'COALESCE(SUM(CASE WHEN action = 2 AND action_type = 4 THEN amount ELSE 0 END), 0) AS out_loan_amount',
                    'COALESCE(SUM(CASE WHEN action = 2 AND action_type = 5 THEN amount ELSE 0 END), 0) AS out_debit_amount',
                    'COALESCE(SUM(CASE WHEN action = 2 AND action_type = 6 THEN amount ELSE 0 END), 0) AS out_held_amount',
                    'currencies.id as currency_id',
                    'currencies.slug as currency_slug',
                ])
            )
            ->whereNotIn('action_type', [3])
            ->where('transactions.created_at', '>=', $from->format('Y-m-d 00:00:00'))
            ->where('transactions.created_at', '<=', $to->format('Y-m-d 23:59:59'))
            ->where('transactions.user_id', '=', $userId)
            ->groupBy('currencies.id')
            ->get();
    }
}
