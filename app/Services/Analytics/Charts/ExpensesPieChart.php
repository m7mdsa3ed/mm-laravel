<?php

namespace App\Services\Analytics\Charts;

use Illuminate\Support\Facades\DB;

class ExpensesPieChart
{
    public function get(): array
    {
        $sql = '
            select
                ifnull(categories.name, "Other") as name
                , accounts.currency_id
                , SUM(IF(action = 1, 0, - amount)) as expenses
            from transactions
            join accounts on accounts.id = transactions.account_id
            left join categories  on categories.id = transactions.category_id
            where month(transactions.created_at) = month(current_date())
            and year(transactions.created_at) = year(current_date())
            and transactions.user_id = :user_id
            and action_type not in (3)
            group by transactions.category_id, accounts.currency_id
            having expenses < 0
        ';

        $results = DB::select($sql, [
            'user_id' => 1,
        ]);

        return collect($results)->groupBy('currency_id')->toArray();
    }
}
