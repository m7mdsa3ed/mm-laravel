<?php

namespace App\Services\Budgets;

use App\Models\Budget;
use Illuminate\Support\Facades\DB;
use Throwable;
use Exception;

class BudgetsService
{
    /** @throws Throwable */
    public function saveBudget(array $data, ?Budget $budget = null): ?Budget
    {
        $isUpdating = !!$budget;

        $budget = $isUpdating ? $budget : new Budget();

        $budget->fill([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'],
            'type' => $data['type'],
            'user_id' => $data['user_id'],
        ]);

        try {
            DB::beginTransaction();

            $budget->save();

            $budget->categories()
                ->sync($data['category_id']);

            DB::commit();

            return $budget;
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function deleteBudget(int $budgetId): ?bool
    {
        try {
            Budget::query()
                ->where('id', $budgetId)
                ->delete();

            return true;
        } catch (Exception) {
            return false;
        }
    }
}
