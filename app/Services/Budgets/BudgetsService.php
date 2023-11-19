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

    public function getAverageAmount(array $array): array
    {
        $categoryIds = $array['categoryIds'];

        $yearly = $array['yearly'] ?? false;

        $forLastMonths = $yearly ? 12 : 6;

        $groupByCondition = $yearly ? 'date_format(created_at, \'%Y\')' : 'date_format(created_at, \'%Y-%m\')';

        $subQuery = DB::table('transactions')
            ->selectRaw('sum(case when action = 1 then amount else -amount end) as balance, category_id')
            ->whereBetween('created_at', [now()->subMonths($forLastMonths - 1)->startOfMonth(), now()->endOfMonth()])
            ->whereIn('category_id', $categoryIds)
            ->groupByRaw('category_id, '. $groupByCondition);

        $results = DB::table('categories')
            ->joinSub($subQuery, 'sub', function ($join) {
                $join->on('categories.id', '=', 'sub.category_id');
            })
            ->addSelect([
                ...array_map(fn ($rawQuery) => DB::raw($rawQuery), [
                    'sum(balance) as total',
                    'count(*) as month_count',
                    'sum(balance) / count(*) as average',
                    'categories.name as category_name',
                ]),
            ])
            ->groupBy('category_id')
            ->get();

        $maxCount = $results->max('month_count');

        $sum = $results->sum(fn ($result) => $result->total);

        return [
            'average' => $sum / $maxCount,
            'total' => $results->sum('total'),
            'data' => $results,
        ];
    }
}
