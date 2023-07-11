<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;

class BalanceQuery
{
    public static function get($userId): mixed
    {
        return DB::table('transactions')
            ->join('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->join('currencies', 'currencies.id', '=', 'accounts.currency_id')
            ->select(
                DB::raw('SUM(CASE WHEN action = 1 THEN amount ELSE -amount END) AS amount'),
                DB::raw('SUM(CASE WHEN action_type IN (4) THEN CASE WHEN action = 1 THEN amount ELSE -amount END ELSE 0 END) * -1 AS loan_amount'),
                DB::raw('SUM(CASE WHEN action_type IN (5) THEN CASE WHEN action = 1 THEN amount ELSE -amount END ELSE 0 END) * -1 AS debit_amount'),
                DB::raw('SUM(CASE WHEN action_type IN (6) THEN CASE WHEN action = 1 THEN amount ELSE -amount END ELSE 0 END) * -1 AS held_amount'),
                'currencies.id AS currency_id',
                'currencies.name AS currency_name'
            )
            ->where('transactions.user_id', '=', $userId)
            ->groupBy('currencies.id')
            ->get();
    }
}
