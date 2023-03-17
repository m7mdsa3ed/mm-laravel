<?php

namespace App\Http\Controllers;

use App\Actions\UpdateCurrencyRates;
use App\Enums\AccountType;
use App\Models\Currency;
use App\Models\User;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class GeneralController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analyticsService
    ) {
    }

    public function stats(Request $request)
    {
        $user = $request->user();

        $currencies = Currency::all()->pluck('name')->toArray();

        $this->updateCurrencyRates($currencies);

        // TODO move the queries to separated service
        return [
            'summary' => $this->getMonthSummary($user),
            'categories_summary' => $this->getCategoriesMonthSummary($user),
            'balance_summary' => $this->getBalanceSummary($user),
            'charts' => $this->analyticsService->getCharts([
                'balance',
                'expensesPie',
            ]),
        ];
    }

    private function getMonthSummary(User $user): array
    {
        $sql = '
            select
                ifnull(sum(if(action = 1, amount, 0)), 0) as in_amount
                , ifnull(sum(if(action = 2, amount, 0)), 0) as out_amount
            from transactions
            where action_type not in (3,4)
            and month(created_at) = month(current_date()) and year(created_at) = year(current_date())
            and transactions.user_id = :user_id
        ';

        return (array) DB::select($sql, [
            'user_id' => $user->id,
        ])[0];
    }

    private function getCategoriesMonthSummary(User $user): array
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
                        'type', transactions.action,
                        'date', date(transactions.created_at)
                    )
                ) as data
            FROM
                transactions
                    LEFT JOIN
                categories ON categories.id = transactions.category_id
            WHERE
                action_type NOT IN (3)
                    AND month(transactions.created_at) = month(current_date()) and year(transactions.created_at) = year(current_date())
                    AND transactions.user_id = :user_id
            GROUP BY transactions.category_id
        ";

        $queryResults = DB::select($sql, [
            'user_id' => $user->id,
        ]);

        return collect($queryResults)
            ->map(fn ($row) => array_merge((array) $row, [
                'data' => json_decode($row->data),
            ]))
            ->toArray();
    }

    private function getBalanceSummary(User $user): array
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
            group by accounts.currency_id
        ';

        return DB::select($sql, [
            'user_id' => $user->id,
        ]);
    }

    private function updateCurrencyRates(array $currencies): void
    {
        if (cache(__FUNCTION__)) {
            return;
        }

        $transformations = [];

        foreach ($currencies as $from) {
            $transformations = [
                ...$transformations,
                ...array_map(fn ($to) => [
                    'From' => $from,
                    'To' => $to,
                ], array_filter($currencies, fn ($to) => $to !== $from)),
            ];
        }

        try {
            foreach ($transformations as $transformation) {
                dispatchAction(new UpdateCurrencyRates($transformation));
            }

            cache()->remember(__FUNCTION__, now()->addMinutes(15)->timestamp - now()->timestamp, fn () => 'CACHE');
        } catch (Exception) {
            cache()->forget(__FUNCTION__);
        }
    }

    public function appInfo()
    {
        $accountTypes = collect(AccountType::cases())
            ->map(function ($case) {
                return [
                    'id' => $case->value,
                    'name' => AccountType::getName($case->value),
                ];
            })
            ->toArray();

        return response()->json([
            'accountTypes' => $accountTypes,
        ]);
    }

    public function getBalanceDetails(Request $request)
    {
        $this->validate($request, [
            'currencyId' => 'required',
        ]);

        $user = $request->user();

        $sql = '
            select
                SUM(IF(action = 1, amount, - amount)) as amount
                , SUM(IF(action_type IN (4), IF(action = 1, amount, - amount), 0)) * - 1 as loan_amount
                , SUM(IF(action_type IN (5), IF(action = 1, amount, - amount), 0)) * - 1 as debit_amount
                , accounts.type_id as account_type_id
                , currencies.name as currency_name
            from transactions
            join accounts on accounts.id = transactions.account_id
            join currencies on currencies.id = accounts.currency_id
            where transactions.user_id = :user_id and accounts.currency_id = :currency_id
            group by accounts.type_id, accounts.currency_id
        ';

        $results = DB::select($sql, [
            'user_id' => $user->id,
            'currency_id' => $request->currencyId,
        ]);

        return collect($results)
            ->map(fn ($row) => [
                ...(array) $row,
                'type' => AccountType::getName($row->account_type_id),
            ])
            ->toArray();
    }
}
