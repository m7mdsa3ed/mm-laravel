<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class GeneralController extends Controller
{
    public function stats()
    {

        $output = (object) [];

        $user_id = Auth::id();
        $startDate = null;
        $endDate = now()->format('Y-m-d');

        $output->user = User::find($user_id)->only('id', 'name', 'email');
        $output->from = $startDate;
        $output->to = $endDate;

        $baseQuery = Transaction::where('user_id', $user_id)
            ->orderBy('created_at', 'DESC');

        $transactions = $baseQuery
            ->with('account', 'category')
            ->when($startDate, fn($query) => $query->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($query) => $query->whereDate('created_at', '<=', $endDate))
            ->get();

        // Balances
        $output->balance = $transactions->sum( fn ($transaction) => $transaction->type == 2 ? $transaction->amount * -1 : $transaction->amount );

        // Total Income
        $output->totalIncome = $transactions->sum( fn ($transaction) => $transaction->type == 1 ? $transaction->amount : 0 );

        // Total Outcome
        $output->totalOutcome = $transactions->sum( fn ($transaction) => $transaction->type == 2 ? $transaction->amount : 0 );

        // Per Account
        $output->perAccount = $transactions->groupBy('account_id')->mapWithKeys( fn ($transactions, $id) => [
            $id => [
                'name'  => $transactions->first()->account->name,
                'in'    => $transactions->sum( fn ($transaction) => $transaction->type == 1 ? $transaction->amount : 0),
                'out'   => $transactions->sum( fn ($transaction) => $transaction->type == 2 ? $transaction->amount : 0),
            ]
        ])->values();

        // Per Category
        $output->perCategory = $transactions->groupBy('category_id')->mapWithKeys( fn ($transactions, $id) => [
            $id => [
                'name'  => $transactions->first()->category->name ?? 'Other',
                'in'    => $transactions->sum( fn ($transaction) => $transaction->type == 1 ? $transaction->amount : 0),
                'out'   => $transactions->sum( fn ($transaction) => $transaction->type == 2 ? $transaction->amount : 0),
            ]
        ])->values();

        // Static Periods ( Not related to the filter )
        $staticPeriod = $baseQuery->get();
        $periods = [0, 7, 30, 90];

        foreach ($periods as $period) {
            $name = $period > 0 ? "last".$period."Days" : 'today';

            $in = $staticPeriod->sum( fn ($transaction) => ($transaction->created_at >= Carbon::today()->subDays($period)) ? ( $transaction->type == 1 ? $transaction->amount : 0 ) : 0 );
            $out = $staticPeriod->sum( fn ($transaction) => ($transaction->created_at >= Carbon::today()->subDays($period)) ? ( $transaction->type == 2 ? $transaction->amount : 0 ) : 0 );
            $balance = $in - $out;

            $output->$name = [
                'in'        => $in,
                'out'       => $out,
                'balance'   => $balance
            ];

        }

        // rates
        $output->ratesPerMonth = $transactions->groupBy( fn($transaction) => $transaction->created_at->format('Y-m'))->mapWithKeys( function($transactions, $yearMonth) {

            $ratesPerCategory = $transactions->groupBy('category_id')->mapWithKeys( function($transactions) use ($yearMonth) {

                $totalIncome = $transactions->sum( fn ($transaction) => $transaction->type == 1 ? $transaction->amount : 0 );
                $totalOutcome = $transactions->sum( fn ($transaction) => $transaction->type == 2 ? $transaction->amount : 0 );

                $daysInMonth = Carbon::parse($yearMonth . '-01')->daysInMonth;

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

            $ratesPerAccount = $transactions->groupBy('account_id')->mapWithKeys( function($transactions) use ($yearMonth) {

                $totalIncome = $transactions->sum( fn ($transaction) => $transaction->type == 1 ? $transaction->amount : 0 );
                $totalOutcome = $transactions->sum( fn ($transaction) => $transaction->type == 2 ? $transaction->amount : 0 );

                $daysInMonth = Carbon::parse($yearMonth . '-01')->daysInMonth;

                $incomeRate = (float) number_format(($totalIncome / $daysInMonth), 2);
                $outcomeRate = (float) number_format(($totalOutcome / $daysInMonth), 2);
                $rate = (float) number_format($incomeRate - $outcomeRate, 2);

                $accountName = $transactions->first()->account->name ?? 'Other';

                return [
                    $accountName => [
                        "totalIncome" => $totalIncome,
                        "totalOutcome" => $totalOutcome,
                        "daysInMonth" => $daysInMonth,
                        "incomeRate" => $incomeRate,
                        "outcomeRate" => $outcomeRate,
                        "rate" => $rate,
                    ]
                ];
            });

            return [
                $yearMonth => [
                    'date' => $yearMonth,
                    'incomeRate' => (float) number_format($ratesPerCategory->sum('incomeRate'), 2),
                    'outcomeRate' => (float) number_format($ratesPerCategory->sum('outcomeRate'), 2),
                    'ratesPerCategory' => $ratesPerCategory,
                    'ratesPerAccount' => $ratesPerAccount,
                ]
            ];
        })->values();

        return $output;
    }
}
