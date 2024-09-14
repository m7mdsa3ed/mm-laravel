<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;

class Last12MonthChartQuery
{
    public static function get($userId): array
    {
        $accountsSubquery = DB::table('accounts')
            ->select(
                'accounts.*',
                'currencies.slug as currency_slug',
                DB::raw('IFNULL(user_currency_rates.rate, currency_rates.rate) as currency_rate')
            )
            ->join('currencies', 'accounts.currency_id', '=', 'currencies.id')
            ->join('currency_rates', function ($join) {
                $join->on('currency_rates.from_currency_id', '=', 'currencies.id')
                    ->where('currency_rates.to_currency_id', '=', 1);
            })
            ->leftJoin('user_currency_rates', 'currency_rates.id', '=', 'user_currency_rates.currency_rate_id');

        return DB::table('transactions')
            ->joinSub($accountsSubquery, 'accounts', function ($join) {
                $join->on('transactions.account_id', '=', 'accounts.id');
            })
            ->select(
                DB::raw('SUM(amount * currency_rate * IF(action = 1, 1, 0)) as revenue'),
                DB::raw('SUM(amount * currency_rate * IF(action = 1, 0, -1)) as expense'),
                DB::raw('YEAR(transactions.created_at) as year'),
                DB::raw('MONTH(transactions.created_at) as month')
            )
            ->whereBetween('transactions.created_at', [
                DB::raw('CURRENT_DATE - INTERVAL 1 YEAR'),
                DB::raw('CURRENT_DATE')
            ])
            ->whereIn('transactions.action_type', [1, 2])
            ->where('transactions.user_id', $userId)
            ->groupBy(DB::raw('YEAR(transactions.created_at)'), DB::raw('MONTH(transactions.created_at)'))
            ->get()
            ->map(fn($row) => [
                'revenue' => $row->revenue,
                'expense' => $row->expense,
                'label' => $row->year . '-' . $row->month,
            ])
            ->toArray();
    }
}
