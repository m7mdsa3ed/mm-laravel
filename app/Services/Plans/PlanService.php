<?php

namespace App\Services\Plans;

use App\Models\Plan;
use App\Models\PlanItem;
use App\Models\PlanItemTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class PlanService
{
    public function getPlan(int $planId): Plan
    {
        return Plan::query()
            ->whereKey($planId)
            ->with('items.transactions')
            ->first();
    }

    public function getPlans(): Collection
    {
        return Plan::query()
            ->with('items')
            ->get();
    }

    /** @throws Throwable */
    public function save(array $data, ?Plan $plan = null): Plan
    {
        $plan ??= new Plan();

        $plan->fill([
            'name' => $data['name'],
            'description' => $data['description'],
        ]);

        try {
            DB::beginTransaction();

            $plan->save();

            $items = $this->getItems($data);

            $plan->items()->saveMany($items);

            DB::commit();

            $plan->loadMissing('items');

            return $plan;
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function newItem(array $data, Plan $plan): PlanItem
    {
        $planItem = new PlanItem();

        $planItem->fill([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
        ]);

        $planItem->plan()->associate($plan);

        $planItem->save();

        return $planItem;
    }

    public function linkPlanItemToTransaction(array $transactionIds, int $planItemId): void
    {
        PlanItemTransaction::query()
            ->where('plan_item_id', $planItemId)
            ->delete();

        $data = array_map(fn ($transactionId) => [
            'plan_item_id' => $planItemId,
            'transaction_id' => $transactionId,
        ], $transactionIds);

        PlanItemTransaction::insert($data);
    }

    /** @throws Throwable */
    public function delete(array $all, Plan $plan): void
    {
        try {
            DB::beginTransaction();

            $plan->items()->delete();

            $plan->delete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * @param array $data
     * @return array<PlanItem>
     */
    private function getItems(array $data): array
    {
        return array_map(fn ($item) => new PlanItem($item), $data['items'] ?? []);
    }
}
