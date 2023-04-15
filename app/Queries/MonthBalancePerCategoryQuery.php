<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;

class MonthBalancePerCategoryQuery
{
    public static function get(int $userId): array
    {
        $sql = "
            SELECT
                categories.id,
                categories.name,
                SUM(IF(action = 1, amount, 0)) AS in_amount,
                SUM(IF(action = 2, amount, 0)) AS out_amount,
                JSON_ARRAYAGG(
                        JSON_OBJECT(
                                'name', transactions.description,
                                'amount', transactions.amount,
                                'currency_name', c.name,
                                'type', transactions.action,
                                'date', date(transactions.created_at)
                            )
                    ) as data
            FROM
                transactions
                    LEFT JOIN
                categories ON categories.id = transactions.category_id
            left join accounts a on transactions.account_id = a.id
            left join currencies c on a.currency_id = c.id
            WHERE
                    action_type NOT IN (3)
              AND month(transactions.created_at) = month(current_date()) and year(transactions.created_at) = year(current_date())
              AND transactions.user_id = :user_id
            GROUP BY transactions.category_id
        ";

        $queryResults = DB::select($sql, [
            'user_id' => $userId,
        ]);

        return collect($queryResults)
            ->map(function ($row) {
                $row->data = collect(json_decode($row->data))
                    ->sortByDesc('date')
                    ->values();

                return $row;
            })
            ->toArray();
    }
}
