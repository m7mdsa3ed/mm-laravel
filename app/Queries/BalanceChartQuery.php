<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;

final class BalanceChartQuery
{
    public static function get(int $userId): array
    {
        $timestampExpression = DB::connection()->getDriverName() === 'pgsql'
            ? 'EXTRACT(EPOCH FROM transactions.created_at)'
            : 'UNIX_TIMESTAMP(transactions.created_at)';

        $results = DB::table('transactions')
            ->join('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->select(
                DB::raw('SUM(CASE WHEN action = 1 THEN amount ELSE -amount END) AS amount'),
                DB::raw("{$timestampExpression} AS timestamp"),
                DB::raw('DATE(transactions.created_at) AS date'),
                'accounts.currency_id'
            )
            ->where('transactions.user_id', '=', $userId)
            ->whereNotIn('action_type', [3])
            ->whereMonth('transactions.created_at', '=', DB::raw('EXTRACT(MONTH FROM CURRENT_DATE)'))
            ->whereYear('transactions.created_at', '=', DB::raw('EXTRACT(YEAR FROM CURRENT_DATE)'))
            ->groupBy(
                'accounts.currency_id',
                DB::raw($timestampExpression),
                DB::raw('DATE(transactions.created_at)')
            )
            ->orderBy('timestamp', 'asc')
            ->get();

        return $results
            ->groupBy('currency_id')
            ->map(function ($group) {
                $cumulativeSum = 0;

                return $group->map(function ($row) use (&$cumulativeSum) {
                    $cumulativeSum += $row->amount;

                    $row->balance = $cumulativeSum;

                    return $row;
                });
            })
            ->toArray();
    }
}
