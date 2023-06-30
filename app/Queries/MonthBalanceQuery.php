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
                DB::raw('COALESCE(SUM(CASE WHEN action = 1 THEN amount ELSE 0 END), 0) AS in_amount'),
                DB::raw('COALESCE(SUM(CASE WHEN action = 2 THEN amount ELSE 0 END), 0) AS out_amount'),
                'currencies.id as currency_id',
                'currencies.slug as currency_slug',
            )
            ->whereNotIn('action_type', [3, 4])
            ->where('transactions.created_at', '>=', $from)
            ->where('transactions.created_at', '<=', $to)
            ->where('transactions.user_id', '=', $userId)
            ->groupBy('currencies.id')
            ->get();
    }
}
