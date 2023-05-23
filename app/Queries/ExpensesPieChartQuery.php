<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;

class ExpensesPieChartQuery
{
    public static function get(int $userId): array
    {
        return DB::table('transactions')
            ->join('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->leftJoin('categories', 'categories.id', '=', 'transactions.category_id')
            ->select(
                'accounts.currency_id',
                DB::raw('COALESCE(categories.name, "Other") as name'),
                DB::raw('SUM(CASE WHEN action = 1 THEN 0 ELSE - amount END) as expenses')
            )
            ->whereMonth('transactions.created_at', '=', date('m'))
            ->whereYear('transactions.created_at', '=', date('Y'))
            ->where('transactions.user_id', '=', $userId)
            ->whereNotIn('action_type', [3])
            ->groupBy('transactions.category_id', 'accounts.currency_id')
            ->havingRaw('expenses < 0')
            ->get()
            ->groupBy('currency_id')
            ->toArray();
    }
}
