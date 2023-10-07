<?php

namespace App\Services\Accounts;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class AccountsService
{
    public function query(): AccountsQueryService
    {
        return app(AccountsQueryService::class);
    }

    public function saveAccount(Request $request, ?Account $account = null): Account
    {
        $account ??= new Account();

        $data = $request->only([
            'name',
            'currency_id',
            'type_id',
            'details',
        ]);

        $account->fill($data);

        $account->user()->associate($request->user()->id);

        $account->save();

        return $account;
    }

    /** @throws Throwable */
    public function deleteAccount(Account $account, int $toAccountId): bool
    {
        try {
            DB::beginTransaction();

            $account->transactions()
                ->update([
                    'account_id' => $toAccountId,
                ]);

            $account->delete();

            DB::commit();

            return true;
        } catch (Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function saveAccountType(mixed $name, User $user, ?AccountType $accountType = null): AccountType
    {
        $accountType ??= new AccountType();

        $accountType->user()->associate($user->id);

        $accountType->fill([
            'name' => $name,
        ]);

        $accountType->save();

        return $accountType;
    }

    public function deleteAccountType(AccountType $accountType): bool
    {
        $accountType->delete();

        return true;
    }
}
