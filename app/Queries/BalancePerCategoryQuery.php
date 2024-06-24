<?php

namespace App\Queries;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BalancePerCategoryQuery
{
    public static function get(int $userId, Carbon $from, Carbon $to): array
    {
        $data = DB::table('transactions')
            ->leftJoin('categories', 'categories.id', '=', 'transactions.category_id')
            ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->leftJoin('currencies', 'currencies.id', '=', 'accounts.currency_id')
            ->select(
                'categories.id',
                'categories.name',
                'currencies.id as currency_id',
                'currencies.slug as currency_slug',
                DB::raw('SUM(CASE WHEN action = 1 THEN amount ELSE 0 END) -  sum(amount * if (action_type = 7, 1, 0)) AS in_amount'),
                DB::raw('SUM(CASE WHEN action = 2 THEN amount ELSE 0 END) -  sum(amount * if (action_type = 7, 1, 0)) AS out_amount'),
                DB::raw('GROUP_CONCAT(DISTINCT transactions.id) AS transaction_ids')
            )
            ->where('transactions.created_at', '>=', $from)
            ->where('transactions.created_at', '<=', $to)
            ->where('transactions.user_id', '=', $userId)
            ->whereNotIn('action_type', [3, 4, 5, 6])
            ->groupBy('transactions.category_id', 'currencies.id')
            ->get();

        $transactionIds = collect($data)
            ->map(function ($row) {
                return explode(',', $row->transaction_ids);
            })
            ->flatten()
            ->toArray();

        $transactions = DB::table('transactions')
            ->whereIn('id', $transactionIds)
            ->get();

        return collect($data)
            ->map(function ($row) use ($transactions) {
                $row->data = collect($transactions)
                    ->whereIn('id', explode(',', $row->transaction_ids))
                    ->toArray();

                return $row;
            })
            ->toArray();
    }
}
