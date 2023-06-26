<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveBudgetRequest;
use App\Models\Budget;
use App\Queries\BudgetsGetAllQuery;
use App\Services\Budgets\BudgetsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetsController extends Controller
{
    public function viewAny(Request $request): JsonResponse
    {
        $user = $request->user();

        $mainCurrency = $user->getMainCurrency();

        $budgets = BudgetsGetAllQuery::get($mainCurrency->id);

        return response()
            ->json($budgets);
    }

    public function save(SaveBudgetRequest $request, BudgetsService $service, ?Budget $budget = null): JsonResponse
    {
        $data = $request->only([
            'name',
            'description',
            'amount',
            'type',
            'category_id',
        ]);

        $data['user_id'] = auth()->id();

        $budget = $service->saveBudget($data, $budget);

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
