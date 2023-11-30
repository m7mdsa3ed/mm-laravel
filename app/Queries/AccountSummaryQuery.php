<?php

namespace App\Queries;

use DB;

class AccountSummaryQuery
{
    public static function get(int $userId, int $accountId, $fromDate = null, $toDate = null): mixed
    {
        return DB::table('transactions')
            ->join('accounts', function ($join) use ($accountId) {
                $join->on('accounts.id', '=', 'transactions.account_id')
                    ->where('accounts.id', '=', $accountId);
            })
            ->when($fromDate, fn ($query) => $query->where('transactions.created_at', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->where('transactions.created_at', '<=', $toDate))
            ->where('accounts.user_id', '=', $userId)
            ->groupBy('accounts.id')
            ->addSelect(
                array_map(fn ($raw) => DB::raw($raw), [
                    'accounts.name',
                    'accounts.id',
                    'sum(if(action = 1, amount, -amount)) as balance',
                    'sum(if(action = 1, amount, 0)) as in_amount',
                    'sum(if(action = 1, 0, amount)) as out_amount',
                    'min(transactions.created_at) as first_transaction_date',
                    'max(transactions.created_at) as last_transaction_date',
                ])
            )
            ->first();
    }
}
