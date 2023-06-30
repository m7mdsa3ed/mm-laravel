<?php

namespace App\Queries;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CategoryPieChartQuery
{
    public static function get(int $userId, Carbon $from, Carbon $to): array
    {
        return DB::table('transactions')
            ->join('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->join('currencies', 'currencies.id', '=', 'accounts.currency_id')
            ->leftJoin('categories', 'categories.id', '=', 'transactions.category_id')
            ->select(
                'categories.id',
                DB::raw('COALESCE(categories.name, "Other") as name'),
                'transactions.action',
                'currencies.id as currency_id',
                'currencies.slug as currency_slug',
                DB::raw('SUM(CASE WHEN action = 1 THEN amount ELSE - amount END) as expenses'),
            )
            ->where('transactions.created_at', '>=', $from)
            ->where('transactions.created_at', '<=', $to)
            ->where('transactions.user_id', '=', $userId)
            ->whereNotIn('action_type', [3])
            ->groupBy('transactions.category_id', 'currencies.id', 'transactions.action')
            ->get()
            ->toArray();
    }
}
