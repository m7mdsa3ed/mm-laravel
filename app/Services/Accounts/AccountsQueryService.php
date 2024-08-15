<?php

namespace App\Services\Accounts;

use App\Models\Account;
use Illuminate\Support\Collection;

class AccountsQueryService
{
    public function getAccount(int $accountId, int $userId)
    {
        return Account::query()
            ->whereKey($accountId)
            ->withBalancies()
            ->where('accounts.user_id', $userId)
            ->with('transactions')
            ->first();
    }

    public function getAccounts(int $userId, array $filters = []): Collection
    {
        $query = Account::query()
            ->where('accounts.user_id', $userId)
            ->withBalancies()
            ->withcount(['transactions' => fn ($query) => $query->withoutGlobalScope('public')])
            ->with('currency', 'type')
            ->orderBy('id', 'asc');

        foreach ($filters as $col => $value) {
            $query->where($col, $value);
        }

        return $query->get();
    }
}
