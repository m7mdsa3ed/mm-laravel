<?php

namespace App\Services\Subscriptions;

use App\Enums\ActionEnum;
use App\Enums\ActionTypeEnum;
use App\Enums\IntervalUnitEnum;
use App\Models\Subscription;
use App\Services\Transactions\DTOs\TransactionData;
use App\Services\Transactions\TransactionMutationService;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class SubscriptionService
{
    /** @throws Throwable */
    public function saveSubscription(
        int $userId,
        string $name,
        float $amount,
        int $accountId,
        int $intervalUnit,
        int $intervalCount,
        bool $autoRenewal = true,
        bool $canCancel = true,
        int $subscriptionId = null,
        ?Carbon $startedAt = null,
    ): Subscription {
        try {
            $expiresAt = Subscription::nextExpirationDate(
                unit: IntervalUnitEnum::from($intervalUnit),
                count: $intervalCount,
                startedAt: $startedAt ?? now(),
            );

            if ($expiresAt->isPast()) {
                throw new Exception('Subscription expires in the past');
            }

            $subscriptionData = [
                'user_id' => $userId,
                'name' => $name,
                'amount' => $amount,
                'interval_unit' => $intervalUnit,
                'interval_count' => $intervalCount,
                'auto_renewal' => $autoRenewal ?? true,
                'can_cancel' => $canCancel ?? true,
                'account_id' => $accountId,
                'expires_at' => $expiresAt,
                'started_at' => $startedAt ?? now(),
            ];

            DB::beginTransaction();

            $subscription = Subscription::updateOrCreate(['id' => $subscriptionId], $subscriptionData);

            DB::commit();

            return $subscription;
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /** @throws Throwable */
    public function renewSubscription(Collection|Subscription $subscriptions): bool
    {
        $subscriptions = $subscriptions instanceof Collection ? $subscriptions : Collection::make([$subscriptions]);

        $currentTimestamp = now();

        $errors = [];

        $subscriptionsToUpdate = $subscriptions
            ->map(function (Subscription $subscription) use ($currentTimestamp, &$errors) {
                if (!$subscription->canRenewBeforeExpiration()) {
                    $errors[] = "{$subscription->name} cannot be renewed before expiration";

                    return null;
                }

                return [
                    ...$subscription->getOriginal(),
                    'expires_at' => $expirationDate = Subscription::nextExpirationDate(
                        unit: $subscription->interval_unit,
                        count: $subscription->interval_count,
                        startedAt: $subscription->expires_at,
                    ),
                    'started_at' => $currentTimestamp,
                    'updated_at' => $currentTimestamp,
                ];
            })
            ->filter();

        if ($subscriptionsToUpdate->isEmpty()) {
            return throw new Exception(implode(PHP_EOL, $errors));
        }

        try {
            DB::beginTransaction();

            Subscription::query()
                ->upsert($subscriptionsToUpdate->toArray(), ['id'], ['expires_at', 'started_at', 'updated_at']);

            $this->createTransactions($subscriptions);

            DB::commit();

            return true;
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /** @throws Throwable */
    public function runSchedule(): void
    {
        info('Running subscription schedule');

        $subscriptions = Subscription::query()
            ->where('auto_renewal', true)
            ->where('expires_at', '<=', now())
            ->where('is_active', true)
            ->get();

        if ($subscriptions->isNotEmpty()) {
            info('Renewing subscriptions', $subscriptions->toArray());

            $this->renewSubscription($subscriptions);
        }
    }

    private function createTransactions(Collection|Subscription $subscriptions): void
    {
        $subscriptions = $subscriptions instanceof Collection ? $subscriptions : Collection::make([$subscriptions]);

        $transactionData = $subscriptions->map(fn($subscription) => new TransactionData(
            action: ActionEnum::OUT->value,
            action_type: ActionTypeEnum::OUTCOME->value,
            amount: $subscription->amount,
            user_id: $subscription->user_id,
            account_id: $subscription->account_id,
            category_id: $subscription->category_id,
            created_at: now()->format('Y-m-d H:i:s'),
            description: $subscription->name . ' subscription renewal',
        ));

        $transactionService = TransactionMutationService::getInstance();

        $transactionService->saveMany(...$transactionData);
    }

    public function cancelSubscription(Subscription $subscription): void
    {
        $subscription->update([
            'is_active' => false,
        ]);
    }

    /** @throws Exception */
    public function reactivateSubscription(Subscription $subscription, $startedAt = null): void
    {
        $expiresAt = Subscription::nextExpirationDate(
            unit: IntervalUnitEnum::from($subscription->interval_unit),
            count: $subscription->interval_count,
            startedAt: $startedAt ?? now(),
        );

        if ($expiresAt->isPast()) {
            throw new Exception('Subscription expires in the past');
        }

        $updateData = [
            'expires_at' => $expiresAt,
            'started_at' => $startedAt ?? now(),
            'is_active' => true,
        ];

        $subscription->update($updateData);
    }

    public function getSubscriptionsThatAboutToExpire(int $days = 7): Collection
    {
        return Subscription::query()
            ->where('expires_at', '<=', now()->addDays($days))
            ->where('is_active', true)
            ->with('user')
            ->get();
    }
}
