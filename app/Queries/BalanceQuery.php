<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;

class BalanceQuery
{
    public static function get($userId): array
    {
        $sql = '
            select
                SUM(IF(action = 1, amount, - amount)) as amount
                , SUM(IF(action_type IN (4), IF(action = 1, amount, - amount), 0)) * - 1 as loan_amount
                , SUM(IF(action_type IN (5), IF(action = 1, amount, - amount), 0)) * - 1 as debit_amount
                , currencies.id currency_id
                , currencies.name currency_name
            from transactions
            join accounts on accounts.id = transactions.account_id
            join currencies on currencies.id = accounts.currency_id
            where transactions.user_id = :user_id
            group by currencies.id
        ';

        return DB::select($sql, [
            'user_id' => $userId,
        ]);
    }
}
