<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveBudgetRequest;
use App\Models\Budget;
use App\Queries\BudgetsGetAllQuery;
use App\Services\Budgets\BudgetsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class BudgetsController extends Controller
{
    public function viewAny(Request $request): JsonResponse
    {
        $user = $request->user();

        $mainCurrency = $user->getMainCurrency();

        $budgets = BudgetsGetAllQuery::get($user->id, $mainCurrency->id);

        return response()
            ->json($budgets);
    }

    /** @throws Throwable */
    public function save(SaveBudgetRequest $request, BudgetsService $service, ?Budget $budget = null): JsonResponse
    {
        $user = auth()->user();

        $data = $request->only([
            'name',
            'description',
            'amount',
            'type',
            'category_id',
        ]);

        $data['user_id'] = $user->id;

        $budget = $service->saveBudget($data, $budget);

        $mainCurrency = $request->user()->getMainCurrency();

        $budget = BudgetsGetAllQuery::get($user->id, $mainCurrency->id, [$budget->id])->first();

        return response()
            ->json($budget);
    }

    public function delete(int $budgetId, BudgetsService $service): JsonResponse
    {
        $successful = $service->deleteBudget($budgetId);

        return response()
            ->json([
                'success' => $successful,
            ]);
    }
}
