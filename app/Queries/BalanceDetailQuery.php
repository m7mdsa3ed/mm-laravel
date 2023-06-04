<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;

class BalanceDetailQuery
{
    public static function get(int $userId, int $currencyId): array
    {
        return DB::table('transactions')
            ->join('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->join('account_types', 'account_types.id', '=', 'accounts.type_id')
            ->join('currencies', 'currencies.id', '=', 'accounts.currency_id')
            ->select(
                DB::raw('SUM(CASE WHEN action = 1 THEN amount ELSE -amount END) AS amount'),
                DB::raw(
                    'SUM(CASE WHEN action_type IN (4) THEN CASE WHEN action = 1 THEN amount ELSE -amount END ELSE 0 END) * -1 AS loan_amount'
                ),
                DB::raw(
                    'SUM(CASE WHEN action_type IN (5) THEN CASE WHEN action = 1 THEN amount ELSE -amount END ELSE 0 END) * -1 AS debit_amount'
                ),
                'account_types.id AS account_type_id',
                'account_types.name AS type',
                'currencies.name AS currency_name'
            )
            ->where('transactions.user_id', '=', $userId)
            ->where('accounts.currency_id', '=', $currencyId)
            ->groupBy('account_types.id', 'currencies.id')
            ->havingRaw('amount > 0 OR loan_amount > 0 OR debit_amount > 0')
            ->get()
            ->toArray();

    }
}
