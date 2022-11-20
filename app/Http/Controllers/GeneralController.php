<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GeneralController extends Controller
{
    public function stats(Request $request)
    {
        // TODO move the queries to separated service
        return [
            'summary' => $this->getMonthSummary(),
            'categories_summary' => $this->getCategoriesMonthSummary(),
            'balance_summary' => $this->getBalanceSummary(),
        ];
    }

    private function getMonthSummary(): array
    {
        $sql = "
            select
                ifnull(sum(if(action = 1, amount, 0)), 0) as in_amount
                , ifnull(sum(if(action = 2, amount, 0)), 0) as out_amount
            from transactions
            where action_type not in (3,4)
            and month(created_at) = month(current_date()) and year(created_at) = year(current_date());
        ";

        return (array)DB::select($sql)[0];
    }

    private function getCategoriesMonthSummary(): array
    {
        $sql = "
            SELECT
                categories.id,
                categories.name,
                SUM(IF(action = 1, amount, 0)) AS in_amount,
                SUM(IF(action = 2, amount, 0)) AS out_amount,
                concat(
                    '[',
                    group_concat(JSON_OBJECT(
                        'name', transactions.description,
                        'amount', transactions.amount,
                        'type', transactions.action,
                        'date', date(transactions.created_at)
                    ))
                    , ']'
                ) as data
            FROM
                transactions
                    LEFT JOIN
                categories ON categories.id = transactions.category_id
            WHERE
                action_type NOT IN (3)
                    AND month(transactions.created_at) = month(current_date()) and year(transactions.created_at) = year(current_date())
            GROUP BY transactions.category_id
        ";

        return collect(DB::select($sql))
            ->map(fn($row) => array_merge((array)$row, [
                'data' => json_decode($row->data)
            ]))
            ->toArray();
    }

    private function getBalanceSummary(): array
    {
        $sql = "
            SELECT
                SUM(IF(action = 1, amount, - amount)) balance,
                SUM(IF(action_type IN (4),
                    IF(action = 1, amount, - amount),
                    0)) * - 1 loan_balance,
                SUM(IF(action_type IN (5),
                    IF(action = 1, amount, - amount),
                    0)) * - 1 debit_balance,
                IF(currency_rates.from_currency_id = 2,
                    SUM(IF(action = 1, amount, - amount)) * currency_rates.rate,
                    SUM(IF(action = 1, amount, - amount))) amount_in_same_currency,
                IF(currency_rates.from_currency_id = 2,
                    currency_rates.rate,
                    1) currency_rate
            FROM
                transactions
                    JOIN
                accounts ON accounts.id = transactions.account_id
                    JOIN
                currency_rates ON currency_rates.from_currency_id = accounts.currency_id
            GROUP BY accounts.currency_id , currency_rates.rate;
        ";

        return DB::select($sql);
    }
}
