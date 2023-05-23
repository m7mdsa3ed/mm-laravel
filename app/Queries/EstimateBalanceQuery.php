<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;

class EstimateBalanceQuery
{
    public function get(int $currencyId, mixed $fromDate): float
    {
        return DB::table('transactions as t')
            ->join('accounts as a', 'a.id', 't.account_id')
            ->whereDate('t.created_at', '>=', $fromDate)
            ->whereNotIn('action_type', [3])
            ->when($currencyId, fn ($query) => $query->where('a.currency_id', $currencyId))
            ->addSelect([
                DB::raw('(cast(ifnull(sum(if(action = 1, amount, - amount)), 0) as decimal(10, 2)))'),
            ])
            ->value('balance');
    }
}
