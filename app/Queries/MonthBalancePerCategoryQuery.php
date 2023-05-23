<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;

class MonthBalancePerCategoryQuery
{
    public static function get(int $userId): array
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
            ->whereMonth('transactions.created_at', '=', date('m'))
            ->whereYear('transactions.created_at', '=', date('Y'))
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
