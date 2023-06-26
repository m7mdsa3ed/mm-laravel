<?php

namespace App\Services\Budgets;

use App\Models\Budget;

class BudgetsService
{
    public function saveBudget(array $data, ?Budget $budget = null): ?Budget
    {
        $isUpdating = !!$budget;

        $budget = $isUpdating ? $budget : new Budget();

        $budget->fill($data);

        $budget->save();

        return $budget;
    }

    public function deleteBudget(int $budgetId): ?bool
    {
        try {
            Budget::query()
                ->where('id', $budgetId)
                ->delete();

            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
