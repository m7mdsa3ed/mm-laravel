<?php

namespace App\Queries;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CategoryDetailsQuery
{
    public static function get(Carbon $from, Carbon $to, ?int $categoryId = null)
    {
        $toMonthDaysCount = $to->daysInMonth;

        $before3MonthsDaysCount = $to->copy()->subMonths(3)->diffInDays($to) ?? (3 * 30);

        $before6MonthsDaysCount = $to->copy()->subMonths(6)->diffInDays($to) ?? (6 * 30);

        return self::getCategoryDetailsQuery($from, $to, $categoryId)
            ->map(function ($data) use ($toMonthDaysCount, $before3MonthsDaysCount, $before6MonthsDaysCount) {
                return (object) [
                    'id' => $data->id,
                    'name' => $data->name,
                    'sevenDaysStats' => [
                        'balance' => $data->balance_7_days,
                        'inAmount' => $data->in_amount_7_days,
                        'outAmount' => $data->out_amount_7_days,
                        'avgBalance' => $data->balance_7_days / 7,
                        'avgInAmount' => $data->in_amount_7_days / 7,
                        'avgOutAmount' => $data->out_amount_7_days / 7,
                    ],
                    'oneMonthStats' => [
                        'balance' => $data->balance_1_month,
                        'inAmount' => $data->in_amount_1_month,
                        'outAmount' => $data->out_amount_1_month,
                        'avgBalance' => $data->balance_1_month / $toMonthDaysCount,
                        'avgInAmount' => $data->in_amount_1_month / $toMonthDaysCount,
                        'avgOutAmount' => $data->out_amount_1_month / $toMonthDaysCount,
                    ],
                    'threeMonthsStats' => [
                        'balance' => $data->balance_3_months,
                        'inAmount' => $data->in_amount_3_months,
                        'outAmount' => $data->out_amount_3_months,
                        'avgBalance' => $data->balance_3_months / $before3MonthsDaysCount,
                        'avgInAmount' => $data->in_amount_3_months / $before3MonthsDaysCount,
                        'avgOutAmount' => $data->out_amount_3_months / $before3MonthsDaysCount,
                    ],
                    'sixMonthsStats' => [
                        'balance' => $data->balance_6_months,
                        'inAmount' => $data->in_amount_6_months,
                        'outAmount' => $data->out_amount_6_months,
                        'avgBalance' => $data->balance_6_months / $before6MonthsDaysCount,
                        'avgInAmount' => $data->in_amount_6_months / $before6MonthsDaysCount,
                        'avgOutAmount' => $data->out_amount_6_months / $before6MonthsDaysCount,
                    ],
                    'oneYearStats' => [
                        'balance' => $data->balance_1_year,
                        'inAmount' => $data->in_amount_1_year,
                        'outAmount' => $data->out_amount_1_year,
                        'avgBalance' => $data->balance_1_year / 365,
                        'avgInAmount' => $data->in_amount_1_year / 365,
                        'avgOutAmount' => $data->out_amount_1_year / 365,
                    ],
                    'twoYearsStats' => [
                        'balance' => $data->balance_2_years,
                        'inAmount' => $data->in_amount_2_years,
                        'outAmount' => $data->out_amount_2_years,
                        'avgBalance' => $data->balance_2_years / (365 * 2),
                        'avgInAmount' => $data->in_amount_2_years / (365 * 2),
                        'avgOutAmount' => $data->out_amount_2_years / (365 * 2),
                    ],
                ];
            });
    }

    private static function getCategoryDetailsQuery(Carbon $from, Carbon $to, ?int $categoryId = null): Collection
    {
        $to = $to->format('Y-m-d');

        $from = $from->format('Y-m-d');

        $selectors = [
            'categories.id',
            'categories.name',
            "sum(if(date(transactions.created_at) >= '$to' - interval 7 day, IF(action = 1, amount, -amount), null) ) AS balance_7_days",
            "sum(if(date(transactions.created_at) >= '$to' - interval 7 day, IF(action = 1, amount, 0), null) ) AS in_amount_7_days",
            "sum(if(date(transactions.created_at) >= '$to' - interval 7 day, IF(action = 1, 0, -amount), null) ) AS out_amount_7_days",
            "sum(if(date(transactions.created_at) >= '$to' - interval 1 month, IF(action = 1, amount, -amount), null) ) AS balance_1_month",
            "sum(if(date(transactions.created_at) >= '$to' - interval 1 month, IF(action = 1, amount, 0), null) ) AS in_amount_1_month",
            "sum(if(date(transactions.created_at) >= '$to' - interval 1 month, IF(action = 1, 0, -amount), null) ) AS out_amount_1_month",
            "sum(if(date(transactions.created_at) >= '$to' - interval 3 month, IF(action = 1, amount, -amount), null) ) AS balance_3_months",
            "sum(if(date(transactions.created_at) >= '$to' - interval 3 month, IF(action = 1, amount, 0), null) ) AS in_amount_3_months",
            "sum(if(date(transactions.created_at) >= '$to' - interval 3 month, IF(action = 1, 0, -amount), null) ) AS out_amount_3_months",
            "sum(if(date(transactions.created_at) >= '$to' - interval 6 month, IF(action = 1, amount, -amount), null) ) AS balance_6_months",
            "sum(if(date(transactions.created_at) >= '$to' - interval 6 month, IF(action = 1, amount, 0), null) ) AS in_amount_6_months",
            "sum(if(date(transactions.created_at) >= '$to' - interval 6 month, IF(action = 1, 0, -amount), null) ) AS out_amount_6_months",
            "sum(if(date(transactions.created_at) >= '$to' - interval 1 year, IF(action = 1, amount, -amount), null) ) AS balance_1_year",
            "sum(if(date(transactions.created_at) >= '$to' - interval 1 year, IF(action = 1, amount, 0), null) ) AS in_amount_1_year",
            "sum(if(date(transactions.created_at) >= '$to' - interval 1 year, IF(action = 1, 0, -amount), null) ) AS out_amount_1_year",
            "sum(if(date(transactions.created_at) >= '$to' - interval 2 year, IF(action = 1, amount, -amount), null) ) AS balance_2_years",
            "sum(if(date(transactions.created_at) >= '$to' - interval 2 year, IF(action = 1, amount, 0), null) ) AS in_amount_2_years",
            "sum(if(date(transactions.created_at) >= '$to' - interval 2 year, IF(action = 1, 0, -amount), null) ) AS out_amount_2_years",
        ];

        return Transaction::query()
            ->addSelect(array_map(fn ($selector) => DB::raw($selector), $selectors))
            ->leftJoin('categories', 'transactions.category_id', '=', 'categories.id')
            ->whereBetween('transactions.created_at', [$from, $to])
            ->groupBy('category_id')
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->get();
    }
}
