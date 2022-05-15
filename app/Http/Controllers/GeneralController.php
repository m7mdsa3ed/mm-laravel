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
        $output = (object) [];

        $user_id = Auth::id();
        $startDate = $request->start_date ?? null;
        $endDate = now()->format('Y-m-d');

        $output->user = User::find($user_id)->only('id', 'name', 'email');
        $output->from = $startDate;
        $output->to = $endDate;

        $baseQuery = Transaction::where('user_id', $user_id)
            ->orderBy('created_at', 'DESC');

        $transactions = $baseQuery
            ->with('account.currencyRate', 'category')
            ->when($startDate, fn ($query) => $query->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('created_at', '<=', $endDate))
            ->get();

        // Balances
        $output->balance = $transactions->sum(function ($transaction) {
            $amount = $transaction->amount;

            if ($transaction->account->currency_id != 1 && $transaction->account->currencyRate) {
                $amount = $transaction->amount * $transaction->account->currencyRate?->rate;
            }

            return $transaction->type == 2 ? $amount * -1 : $amount;
        });

        $output->balancePerAccount = (clone $baseQuery)->withoutGlobalScopes(['public'])->select('type', 'amount', 'account_id')->get()->groupBy('account_id')->mapWithKeys(function ($transactions, $id) {

            $accountName = $transactions->first()->account->name;

            $in = $transactions->sum(fn ($transaction) => $transaction->type == 1 ? $transaction->amount : 0);
            $out = $transactions->sum(fn ($transaction) => $transaction->type == 2 ? $transaction->amount : 0);
            $balance = $in - $out;

            return [
                $id => [
                    'name' => $accountName,
                    'balance' => $balance,
                ]
            ];
        })->values();

        // Total Income
        $output->totalIncome = $transactions->sum(fn ($transaction) => $transaction->type == 1 ? $transaction->amount : 0);

        // Total Outcome
        $output->totalOutcome = $transactions->sum(fn ($transaction) => $transaction->type == 2 ? $transaction->amount : 0);

        // Per Account
        $output->perAccount = $transactions->groupBy('account_id')->mapWithKeys(fn ($transactions, $id) => [
            $id => [
                'name'  => $transactions->first()->account->name,
                'in'    => $transactions->sum(fn ($transaction) => $transaction->type == 1 ? $transaction->amount : 0),
                'out'   => $transactions->sum(fn ($transaction) => $transaction->type == 2 ? $transaction->amount : 0),
            ]
        ])->values();

        // Per Category
        $output->perCategory = $transactions->groupBy('category_id')->mapWithKeys(fn ($transactions, $id) => [
            $id => [
                'name'  => $transactions->first()->category->name ?? 'Other',
                'in'    => $transactions->sum(fn ($transaction) => $transaction->type == 1 ? $transaction->amount : 0),
                'out'   => $transactions->sum(fn ($transaction) => $transaction->type == 2 ? $transaction->amount : 0),
            ]
        ])->values();

        // Static Periods ( Not related to the filter )
        $staticPeriod = (clone $baseQuery)->select('type', 'amount', 'created_at')->get();
        $periods = [0, 7, 30, 90];

        foreach ($periods as $period) {
            $name = $period > 0 ? "last" . $period . "Days" : 'today';

            $in = $staticPeriod->sum(fn ($transaction) => ($transaction->created_at >= Carbon::today()->subDays($period)) ? ($transaction->type == 1 ? $transaction->amount : 0) : 0);
            $out = $staticPeriod->sum(fn ($transaction) => ($transaction->created_at >= Carbon::today()->subDays($period)) ? ($transaction->type == 2 ? $transaction->amount : 0) : 0);
            $balance = $in - $out;

            $output->$name = [
                'in'        => $in,
                'out'       => $out,
                'balance'   => $balance
            ];
        }

        // rates
        $output->ratesPerMonth = $this->getRatesPerMonth($transactions);

        return $output;
    }

    private function getRatesPerMonth($transactions)
    {
        return $transactions->groupBy(fn ($transaction) => $transaction->created_at->format('Y-m'))->mapWithKeys(function ($transactions, $yearMonth) {

            $daysInMonth = Carbon::parse($yearMonth . '-01')->daysInMonth;

            $ratesPerCategory = $transactions->groupBy('category_id')->mapWithKeys(function ($transactions) use ($daysInMonth) {

                $totalIncome = $transactions->sum(fn ($transaction) => $transaction->type == 1 ? $transaction->amount : 0);
                $totalOutcome = $transactions->sum(fn ($transaction) => $transaction->type == 2 ? $transaction->amount : 0);

                $incomeRate = (float) number_format(($totalIncome / $daysInMonth), 2);
                $outcomeRate = (float) number_format(($totalOutcome / $daysInMonth), 2);
                $rate = (float) number_format($incomeRate - $outcomeRate, 2);

                $categoryName = $transactions->first()->category->name ?? 'Other';

                return [
                    $categoryName => [
                        "totalIncome" => $totalIncome,
                        "totalOutcome" => $totalOutcome,
                        "daysInMonth" => $daysInMonth,
                        "incomeRate" => $incomeRate,
                        "outcomeRate" => $outcomeRate,
                        "rate" => $rate,
                    ]
                ];
            });

            $ratesPerAccount = $transactions->groupBy('account_id')->mapWithKeys(function ($transactions) use ($daysInMonth) {

                $totalIncome = $transactions->sum(fn ($transaction) => $transaction->type == 1 ? $transaction->amount : 0);
                $totalOutcome = $transactions->sum(fn ($transaction) => $transaction->type == 2 ? $transaction->amount : 0);

                $incomeRate = (float) number_format(($totalIncome / $daysInMonth), 2);
                $outcomeRate = (float) number_format(($totalOutcome / $daysInMonth), 2);
                $rate = (float) number_format($incomeRate - $outcomeRate, 2);

                $accountName = $transactions->first()->account->name ?? 'Other';

                return [
                    $accountName => [
                        "totalIncome" => $totalIncome,
                        "totalOutcome" => $totalOutcome,
                        "incomeRate" => $incomeRate,
                        "outcomeRate" => $outcomeRate,
                        "rate" => $rate,
                    ]
                ];
            });

            return [
                $yearMonth => [
                    'date' => $yearMonth,
                    "daysInMonth" => $daysInMonth,
                    'incomeRate' => (float) number_format($ratesPerCategory->sum('incomeRate'), 2),
                    'outcomeRate' => (float) number_format($ratesPerCategory->sum('outcomeRate'), 2),
                    'ratesPerCategory' => $ratesPerCategory,
                    'ratesPerAccount' => $ratesPerAccount,
                ]
            ];
        })->values();
    }

    public function whenToGet(Request $request)
    {

        $this->validate($request, [
            'amount'    => 'required|numeric'
        ]);

        $user = Auth::user();

        // TODO Get my accounts balances and adjust the needed amount by 'em
        // TODO Consider the safe amount as well
        // TODO Check whether the needed amount can be afforded

        $startDate = Carbon::today()->subMonths(6);

        if ($request->filled('after')) {
            $startDate = Carbon::parse($request->after . "-01");
        }

        $monthlyAvg = $request->avg ?? false;

        if ($monthlyAvg == false) {

            $transactions = Transaction::where('user_id', $user->id)->orderBy('created_at', 'DESC')
                ->whereDate('created_at', ">=", $startDate)
                ->get();

            $ratesPerMonth = $this->getRatesPerMonth($transactions);

            $monthlyAvg = $ratesPerMonth->map(function ($rate) {
                return $rate['daysInMonth'] * ($rate['incomeRate'] - $rate['outcomeRate']);
            })->avg();
        }

        return [
            'approx' => Carbon::today()->addMonths(ceil($request->amount / $monthlyAvg))->diffForHumans(null, null, false, 2),
            'monthlyAvg' => $monthlyAvg
        ];
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();

        $sql = "
            with data as (
                select
                    sum(if(type = 1, amount, -amount)) month_balance,
                    sum(if(type = 1, amount, 0)) income_amount,
                    sum(if(type = 1, 0, amount)) spent_amount,
                    concat( monthname(created_at), ', ', year(created_at))  month_year,
                    month(created_at) month_num,
                    year(created_at) year
                from transactions
                where user_id = :user_id and is_public = 1
                    -- and ( year(created_at) != year(now()) and month(created_at) != month(now()))
                group by year, month_num, month_year
                order by year, month_num asc
            )

            select
                sum(month_balance) over (order by year, month_num asc) as cumulative_month_balance,
                income_amount,
                spent_amount,
                month_year
            from data;
        ";

        $results = DB::select($sql, [
            'user_id' => $user->id
        ]);

        $results = collect($results);

        $balanceChart = $results->map(fn ($row) => [
            'date' => $row->month_year,
            'amount' => $row->cumulative_month_balance
        ]);

        $incomeChart = $results->map(fn ($row) => [
            'date' => $row->month_year,
            'amount' => $row->income_amount
        ]);

        $spendChart = $results->map(fn ($row) => [
            'date' => $row->month_year,
            'amount' => $row->spent_amount
        ]);

        $sql = "
            select
                ifnull(categories.name, 'Other') name,
                sum(if(transactions.type = 1, transactions.amount, -transactions.amount)) balance,
                concat( month(transactions.created_at), ', ', year(transactions.created_at)) month_year,
                month(transactions.created_at) month_num,
                year(transactions.created_at) year
            from transactions
            left join categories on categories.id = transactions.category_id
            where transactions.user_id = :user_id and transactions.is_public = 1
            group by year, month_num, month_year, transactions.category_id
            having balance < 0
            order by year, month_num asc;
        ";

        $monthlySpendPerCategory = DB::select($sql, [
            'user_id' => $user->id
        ]);

        $monthlySpendPerCategory = collect($monthlySpendPerCategory);
        $monthlySpendPerCategoryLabels = $monthlySpendPerCategory->pluck('month_year')->unique()->values();
        $monthlySpendPerCategoryData = $monthlySpendPerCategory->groupBy('name')->mapWithKeys(function ($group, $name) use ($monthlySpendPerCategoryLabels) {
            $data = [];

            foreach ($monthlySpendPerCategoryLabels as $label) {
                $value = $group->where('month_year', $label)->first()?->balance ?? 0;

                $data[] = ($value * -1);
            }

            return [$name => [
                'name' => $name,
                'data' => $data
            ]];
        })->values();

        return [
            'balanceChart' => $balanceChart,
            "incomeChart" => $incomeChart,
            "spendChart" => $spendChart,
            'monthlySpendPerCategory' => [
                'labels' => $monthlySpendPerCategoryLabels,
                'series' => $monthlySpendPerCategoryData
            ]
        ];
    }
}
