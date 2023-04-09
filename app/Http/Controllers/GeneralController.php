<?php

namespace App\Http\Controllers;

use App\Actions\Deploy;
use App\Actions\UpdateCurrencyRates;
use App\Enums\AccountType;
use App\Models\Currency;
use App\Models\User;
use App\Services\Analytics\AnalyticsService;
use App\Services\App\AppService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\JsonResponse;
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

        $currencies = Currency::getSlugs()->toArray();

        $this->updateCurrencyRates($currencies);

        // TODO move the queries to separated service
        return [
            'summary' => $this->getMonthSummary($user),
            'categories_summary' => $this->getCategoriesMonthSummary($user),
            'balance_summary' => $this->getBalanceSummary($user),
            'pinned_accounts' => settings('pinnedAccounts', $user->id),
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
                sum(in_amount * ifnull(rate, 1)) in_amount
                 , sum(out_amount * ifnull(rate, 1)) out_amount
            from (
                select ifnull(sum(if(action = 1, amount, 0)), 0) as in_amount
                   , ifnull(sum(if(action = 2, amount, 0)), 0) as out_amount
                   , cr.rate
                from transactions t
                       join accounts a on t.account_id = a.id
                       left join currency_rates cr on a.currency_id = cr.from_currency_id and cr.to_currency_id = :main_currency_id
                where action_type not in (3, 4)
                and month(t.created_at) = month(current_date())
                and year(t.created_at) = year(current_date())
                and t.user_id = :user_id
                group by a.currency_id, cr.rate
            ) sub
        ';

        return (array) DB::select($sql, [
            'user_id' => $user->id,
            'main_currency_id' => Currency::whereName('EGP')->value('id'),
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
            'user_id' => $user->id,
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
            group by currencies.id
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

        $transformations = Currency::getTransformationsFromCurrencies($currencies);

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
        $info = AppService::getInstance()
            ->info();

        return response()->json($info);
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
            group by accounts.type_id, currencies.id
            having amount > 0 or loan_amount > 0 or debit_amount > 0
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

    public function deploy(Deploy $deploy)
    {
        liveResponse(fn () => $deploy->execute(true));

        return response()
            ->json([
                'message' => 'Deploy Action Executed',
            ]);
    }

    public function downloadDatabase(AppService $appService)
    {
        $url = $appService->downloadDatabase();

        return response()->json([
            'url' => $url,
        ]);
    }

    public function getSettings(Request $request)
    {
        return response()->json([
            'settings' => settings([]),
        ]);
    }

    public function saveSettings(Request $request, SettingsService $settingsService): JsonResponse
    {
        $this->validate($request, [
            'key' => 'required',
            'value' => 'required',
        ]);

        $successful = $settingsService->save(...$request->only(['key', 'value']));

        return response()->json([
            'status' => $successful ? 'success' : 'failed',
        ]);
    }
}
