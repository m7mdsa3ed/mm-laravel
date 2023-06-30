<?php

namespace App\Http\Controllers;

use App\Actions\Deploy;
use App\Actions\UpdateCurrencyRatesBulk;
use App\Models\Currency;
use App\Queries\BalanceChartQuery;
use App\Queries\BalanceDetailQuery;
use App\Queries\BalanceByMainCurrency;
use App\Queries\BalanceQuery;
use App\Queries\CategoryPieChartQuery;
use App\Queries\BalancePerCategoryQuery;
use App\Queries\MonthBalanceQuery;
use App\Services\App\AppService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Validation\ValidationException;

class GeneralController extends Controller
{
    public function __construct()
    {
    }

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $mainCurrencyId = $user->getMainCurrency()->id;

        $currencies = Currency::getSlugs()->toArray();

        $currencyRatesUpdated = $this->updateCurrencyRates($currencies);

        $fromDate = $request->date('from') ?? now()->startOfMonth();

        $toDate = $request->date('to') ?? now();

        return response()->json([
            'summary' => MonthBalanceQuery::get($user->id, $mainCurrencyId, $fromDate, $toDate),
            'categories_summary' => BalancePerCategoryQuery::get($user->id, $fromDate, $toDate),
            'balance_summary' => BalanceQuery::get($user->id),
            'pinned_accounts' => settings('pinnedAccounts', $user->id),
            'charts' => [
                'balance' => BalanceChartQuery::get($user->id, $fromDate, $toDate),
                'categoryPie' => CategoryPieChartQuery::get($user->id, $fromDate, $toDate),
            ],
            'balanceByMainCurrency' => BalanceByMainCurrency::get($user->id, $mainCurrencyId),
            'currencyRatesUpdated' => $currencyRatesUpdated,
        ]);
    }

    private function updateCurrencyRates(array $currencies): bool
    {
        if (cache(__FUNCTION__)) {
            return false;
        }

        try {
            $success = dispatchAction(new UpdateCurrencyRatesBulk());

            cache()->remember(__FUNCTION__, now()->addMinutes(15)->timestamp - now()->timestamp, fn () => 'CACHE');

            return $success;
        } catch (Exception) {
            cache()->forget(__FUNCTION__);

            return false;
        }
    }

    public function appInfo(): JsonResponse
    {
        $info = AppService::getInstance()
            ->info();

        return response()->json($info);
    }

    public function getBalanceDetails(Request $request): JsonResponse
    {
        $this->validate($request, [
            'currencyId' => 'required',
        ]);

        $user = $request->user();

        $balanceDetails = BalanceDetailQuery::get($user->id, $request->currencyId);

        return response()->json($balanceDetails);
    }

    public function deploy(Deploy $deploy): JsonResponse
    {
        liveResponse(fn () => $deploy->execute(true));

        return response()
            ->json([
                'message' => 'Deploy Action Executed',
            ]);
    }

    public function downloadDatabase(AppService $appService): JsonResponse
    {
        try {
            $url = $appService->downloadDatabase();

            return response()->json([
                'url' => $url,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => "Couldn't download database, please try again later. Error: {$e->getMessage()}",
            ], 500);
        }
    }

    public function getSettings(): JsonResponse
    {
        return response()->json([
            'settings' => settings([]),
        ]);
    }

    /** @throws ValidationException */
    public function saveSettings(Request $request, SettingsService $settingsService): JsonResponse
    {
        $this->validate($request, [
            'key' => 'required',
        ]);

        $successful = $settingsService->save(...$request->only(['key', 'value']));

        return response()->json([
            'status' => $successful ? 'success' : 'failed',
        ]);
    }

    public function getEstimate(Request $request): array
    {
        $avg = $request->get('avgAmount');

        if (!$avg) {
            $request->validate([
                'fromDate' => 'required',
                'currencyId' => 'required',
            ]);
        }

        if (!$avg) {
            $fromDate = $request->date('fromDate');

            $currencyId = $request->get('currencyId');

            $adjustAmount = $request->get('adjustAmount', 0);

            $balance = (new \App\Queries\EstimateBalanceQuery())->get(
                currencyId: $currencyId,
                fromDate: $fromDate,
            );

            $avg = ($balance - $adjustAmount) / now()->diff($fromDate)->m;
        }

        $neededAmount = $request->get('neededAmount');

        $monthsNeeded = $neededAmount / $avg;

        return [
            'neededAmount' => $neededAmount,
            'estimatedAvgPerMonth' => $avg,
            'estimatedAvgPerYear' => $avg * 12,
            'estimatedMonthsNeeded' => $monthsNeeded,
            'estimatedYearsNeeded' => $monthsNeeded / 12,
        ];
    }
}
