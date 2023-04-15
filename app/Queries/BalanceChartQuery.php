<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;

final class BalanceChartQuery
{
    public static function get(int $userId): array
    {
        $sql = '
             select
                 SUM(IF(action = 1, amount, - amount)) as amount
                  , unix_timestamp(transactions.created_at) as timestamp
                  , date(transactions.created_at) as date
                  , accounts.currency_id
             from transactions
                      join accounts on accounts.id = transactions.account_id
             where transactions.user_id = :user_id
               and action_type not in (3)
               and month(transactions.created_at) = month(current_date())
               and year(transactions.created_at) = year(current_date())
             group by accounts.currency_id, unix_timestamp(transactions.created_at), date(transactions.created_at)
             order by timestamp asc
        ';

        $results = DB::select($sql, [
            'user_id' => $userId,
        ]);

        return collect($results)
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
