<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;

class ExpensesPerMonthQuery
{
    public static function get(string $userId, int $currencyId, int $monthsCount = 12): array
    {
        $sql = '
            select (cast(SUM(IF(action = 1, amount, - amount) * ifnull(cr.rate, 1)) as decimal(10, 4))) as amount
                 , month(transactions.created_at) month
                 , year(transactions.created_at) year
            from transactions
                     join accounts a on transactions.account_id = a.id
                     left join currency_rates cr on a.currency_id = cr.from_currency_id and cr.to_currency_id = :to_currency_id
            where date(transactions.created_at) >= date(current_date() - interval :months_count month)
              and transactions.action = 2
              and action_type != 3
              and transactions.user_id = :user_id
            group by month(transactions.created_at), year(transactions.created_at)
        ';

        return DB::select($sql, [
            'user_id' => $userId,
            'to_currency_id' => $currencyId,
            'months_count' => $monthsCount,
        ]);
    }
}
