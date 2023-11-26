<?php

namespace App\Http\Controllers;

use App\Http\Requests\Subscriptions\SaveSubscriptionRequest;
use App\Models\Subscription;
use App\Services\Subscriptions\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Throwable;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {
    }

    public function viewAny(): JsonResponse
    {
        $userId = auth()->id();

        $subscriptions = Subscription::query()
            ->where('user_id', $userId)
            ->get();

        return response()->json($subscriptions);
    }

    public function saveSubscription(SaveSubscriptionRequest $request, ?int $subscriptionId = null): JsonResponse
    {
        $userId = auth()->id();

        try {
            $subscription = $this->subscriptionService->saveSubscription(
                userId: $userId,
                name: $request->input('name'),
                amount: $request->input('amount'),
                accountId: $request->input('account_id'),
                intervalUnit: $request->input('interval_unit'),
                intervalCount: $request->input('interval_count'),
                autoRenewal: $request->boolean('auto_renewal', true),
                canCancel: $request->boolean('can_cancel', true),
                subscriptionId: $subscriptionId,
                startedAt: $request->date('started_at'),
            );

            return response()->json($subscription);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Subscription save failed'], 500);
        }
    }

    public function renewSubscription($subscriptionId): JsonResponse
    {
        $subscription = Subscription::findOrFail($subscriptionId);

        try {
            $this->subscriptionService->renewSubscription($subscription);

            return response()->json($subscription->fresh());
        } catch (Throwable $e) {
            return response()->json(['message' => 'Subscription renewal failed'], 500);
        }
    }

    public function cancelSubscription($subscriptionId): JsonResponse
    {
        $subscription = Subscription::findOrFail($subscriptionId);

        try {
            $this->subscriptionService->cancelSubscription($subscription);

            return response()->json($subscription->fresh());
        } catch (Throwable $e) {
            return response()->json(['message' => 'Subscription cancellation failed'], 500);
        }
    }

    public function reactivateSubscription(Subscription $subscription)
    {
        try {
            $this->subscriptionService->reactivateSubscription($subscription);

            return response()->json($subscription->fresh());
        } catch (Throwable $e) {
            return response()->json(['message' => 'Subscription reactivation failed'], 500);
        }
    }

    public function deleteSubscription($subscriptionId): JsonResponse
    {
        $subscription = Subscription::findOrFail($subscriptionId);

        $subscription->delete();

        return response()->json(['message' => 'Subscription deleted successfully']);
    }
}
