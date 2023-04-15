<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;

class MonthBalanceQuery
{
    public static function get(int $userId, int $currencyId): array
    {
        $sql = '
            select
                sum(in_amount * ifnull(rate, 1)) in_amount
                 , sum(out_amount * ifnull(rate, 1)) out_amount
            from (
                select ifnull(sum(if(action = 1, amount, 0)), 0) as in_amount
                   , ifnull(sum(if(action = 2, amount, 0)), 0) as out_amount
                   , cr.rate
                from transactions t
                       join accounts a on t.account_id = a.id
                       left join currency_rates cr on a.currency_id = cr.from_currency_id and cr.to_currency_id = :main_currency_id
                where action_type not in (3, 4)
                and month(t.created_at) = month(current_date())
                and year(t.created_at) = year(current_date())
                and t.user_id = :user_id
                group by a.currency_id, cr.rate
            ) sub
        ';

        return (array) DB::select($sql, [
            'user_id' => $userId,
            'main_currency_id' => $currencyId,
        ])[0];
    }
}
