<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;

class BalanceByMainCurrency
{
    public static function get(int $userId, int $toCurrencyId): object
    {
        $sql = '
            select sum(amount * currency_rate)       as amount
                 , sum(loan_amount * currency_rate)  as loan_amount
                 , sum(debit_amount * currency_rate) as debit_amount
            from (select SUM(IF(action = 1, amount, - amount))                                  as amount
                       , SUM(IF(action_type IN (4), IF(action = 1, amount, - amount), 0)) * - 1 as loan_amount
                       , SUM(IF(action_type IN (5), IF(action = 1, amount, - amount), 0)) * - 1 as debit_amount
                       , ifnull(min(cr.rate), 1)                                                   currency_rate
                  from transactions
                           join accounts on accounts.id = transactions.account_id
                           join currencies on currencies.id = accounts.currency_id
                           left join currency_rates cr on currencies.id = cr.from_currency_id and cr.to_currency_id = :to_currency_id
                  where transactions.user_id = :user_id
                  group by currencies.id) sub
        ';

        return DB::select($sql, [
            'user_id' => $userId,
            'to_currency_id' => $toCurrencyId,
        ])[0];
    }
}
