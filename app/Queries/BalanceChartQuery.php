<?php

namespace App\Queries;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class BalanceChartQuery
{
    public static function get(int $userId, Carbon $from, Carbon $to): array
    {
        $timestampExpression = DB::connection()->getDriverName() === 'pgsql'
            ? 'EXTRACT(EPOCH FROM transactions.created_at)'
            : 'UNIX_TIMESTAMP(transactions.created_at)';

        $results = DB::table('transactions')
            ->join('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->join('currencies', 'currencies.id', '=', 'accounts.currency_id')
            ->select(
                DB::raw('SUM(CASE WHEN action = 1 THEN amount ELSE -amount END) AS amount'),
                DB::raw("{$timestampExpression} AS timestamp"),
                DB::raw('DATE(transactions.created_at) AS date'),
                'currencies.id as currency_id',
                'currencies.slug as currency_slug',
            )
            ->where('transactions.user_id', '=', $userId)
            ->whereNotIn('action_type', [3])
            ->where('transactions.created_at', '>=', $from)
            ->where('transactions.created_at', '<=', $to)
            ->groupBy(
                'currencies.id',
                DB::raw($timestampExpression),
                DB::raw('DATE(transactions.created_at)')
            )
            ->orderBy('timestamp', 'asc')
            ->get();

        return $results
            ->groupBy('currency_slug')
            ->map(function ($group) {
                $cumulativeSum = 0;

                return $group->map(function ($row) use (&$cumulativeSum) {
                    $cumulativeSum += $row->amount;

                    $row->balance = $cumulativeSum;

                    return $row;
                });
            })
            ->flatten(1)
            ->toArray();
    }
}
