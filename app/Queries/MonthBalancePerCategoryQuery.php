<?php

namespace App\Queries;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MonthBalancePerCategoryQuery
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
                DB::raw('SUM(CASE WHEN action = 1 THEN amount ELSE 0 END) AS in_amount'),
                DB::raw('SUM(CASE WHEN action = 2 THEN amount ELSE 0 END) AS out_amount')
            )
            ->where('transactions.created_at', '>=', $from)
            ->where('transactions.created_at', '<=', $to)
            ->where('transactions.user_id', '=', $userId)
            ->whereNotIn('action_type', [3])
            ->groupBy('transactions.category_id')
            ->get();

        return collect($data)
            ->map(function ($row) {
                $row->data = [];

                return $row;
            })
            ->toArray();
    }
}
