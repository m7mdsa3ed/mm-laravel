<?php

namespace App\Http\Controllers;

use App\Services\Plans\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class PlansController extends Controller
{
    public function viewAny(PlanService $planService): JsonResponse
    {
        $plans = $planService->getPlans();

        return response()->json($plans);
    }

    public function viewOne(PlanService $planService, int $planId): JsonResponse
    {
        $plan = $planService->getPlan($planId);

        return response()->json($plan);
    }

    /** @throws Throwable */
    public function save(Request $request, PlanService $planService, ?int $planId = null): JsonResponse
    {
        if ($planId) {
            $plan = $planService->getPlan($planId);
        }

        $plan = $planService->save($request->all(), $plan ?? null);

        return response()->json($plan);
    }

    public function newItem(Request $request, PlanService $planService, int $planId): JsonResponse
    {
        $plan = $planService->getPlan($planId);

        $planItem = $planService->newItem($request->all(), $plan);

        return response()->json($planItem);
    }

    public function linkPlanItemToTransaction(Request $request, PlanService $planService, int $planItemId): JsonResponse
    {
        $planService->linkPlanItemToTransaction($request->get('transaction_ids'), $planItemId);

        return response()->json([
            'message' => 'success',
        ]);
    }

    /** @throws Throwable */
    public function delete(Request $request, PlanService $planService, int $planId): Response
    {
        $plan = $planService->getPlan($planId);

        $planService->delete($request->all(), $plan);

        return response()->noContent();
    }
}
