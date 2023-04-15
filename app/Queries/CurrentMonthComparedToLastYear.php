<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;

class CurrentMonthComparedToLastYear
{
    public static function get(int $userId, int $currencyId): array
    {
        $sql = '
            select (cast(SUM(IF(action = 1, amount, - amount) * ifnull(cr.rate, 1)) as decimal(10, 4))) as amount
                 , month(transactions.created_at) month
                 , year(transactions.created_at) year
            from transactions
                     join accounts a on transactions.account_id = a.id
                     left join currency_rates cr on a.currency_id = cr.from_currency_id and cr.to_currency_id = :to_currency_id
            where (month(transactions.created_at) = month(current_date()) or
                   month(transactions.created_at) = month(current_date() - interval 1 year))
              and (year(transactions.created_at) = year(current_date()) or
                   year(transactions.created_at) = year(current_date() - interval 1 year))
              and transactions.action = 2
              and action_type != 3
              and transactions.user_id = :user_id
            group by month(transactions.created_at), year(transactions.created_at);
        ';

        return DB::select($sql, [
            'user_id' => $userId,
            'to_currency_id' => $currencyId,
        ]);
    }
}
