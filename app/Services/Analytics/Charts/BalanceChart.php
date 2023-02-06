<?php

namespace App\Services\Analytics\Charts;

use Illuminate\Support\Facades\DB;

final class BalanceChart
{
    public function get(): array
    {
        $sql = '
            select
                *
                , unix_timestamp(date) as timestamp
                , sum(amount) over(partition by currency_id order by date asc) as balance
            from (
                select
                    SUM(IF(action = 1, amount, - amount)) as amount
                    , date(transactions.created_at) as date
                    , accounts.currency_id
                from transactions
                join accounts on accounts.id = transactions.account_id
                where transactions.user_id = :user_id
                and action_type not in (3)
                group by accounts.currency_id, date(transactions.created_at)
            ) as sub
            order by date desc
        ';

        $results = DB::select($sql, [
            'user_id' => 1,
        ]);

        return collect($results)->groupBy('currency_id')->toArray();
    }
}
