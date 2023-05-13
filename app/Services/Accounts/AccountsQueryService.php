<?php

namespace App\Services\Accounts;

use App\Models\Account;

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

    public function getAccounts(int $userId)
    {
        return Account::query()
            ->where('accounts.user_id', $userId)
            ->withBalancies()
            ->withcount(['transactions' => fn ($query) => $query->withoutGlobalScope('public')])
            ->with('currency', 'type')
            ->orderBy('id', 'asc')
            ->get();
    }
}
