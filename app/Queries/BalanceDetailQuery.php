<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;

class BalanceDetailQuery
{
    public static function get(int $userId, int $currencyId): array
    {
        $sql = '
            select
                SUM(IF(action = 1, amount, - amount)) as amount
                , SUM(IF(action_type IN (4), IF(action = 1, amount, - amount), 0)) * - 1 as loan_amount
                , SUM(IF(action_type IN (5), IF(action = 1, amount, - amount), 0)) * - 1 as debit_amount
                , accounts.type_id as account_type_id
                , account_types.name as type
                , currencies.name as currency_name
            from transactions
            join accounts on accounts.id = transactions.account_id
            join account_types on account_types.id = accounts.type_id
            join currencies on currencies.id = accounts.currency_id
            where transactions.user_id = :user_id and accounts.currency_id = :currency_id
            group by accounts.type_id, currencies.id
            having amount > 0 or loan_amount > 0 or debit_amount > 0
        ';

        return DB::select($sql, [
            'user_id' => $userId,
            'currency_id' => $currencyId,
        ]);
    }
}
