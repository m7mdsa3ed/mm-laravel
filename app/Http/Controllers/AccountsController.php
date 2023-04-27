<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountType;
use App\Services\Settings\SettingsService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Exception;

class AccountsController extends Controller
{
    public function viewAny()
    {
        return Account::query()
            ->where('accounts.user_id', auth()->id())
            ->withBalancies()
            ->withcount(['transactions' => fn ($query) => $query->withoutGlobalScope('public')])
            ->with('currency', 'type')
            ->orderBy('id', 'asc')
            ->get();
    }

    /** @throws ValidationException */
    public function save(Request $request, Account $account = null)
    {
        $this->validate($request, [
            'currency_id' => 'required|exists:currencies,id',
        ]);

        $account ??= new Account();

        $data = $request->only([
            'name',
            'currency_id',
            'type_id',
        ]);

        $account->fill($data);

        $account->user()->associate(auth()->user());

        $account->save();

        $account
            ->loadMissing([
                'currency',
                'user',
                'type',
            ])
            ->loadCount([
                'transactions' => fn ($query) => $query->withoutGlobalScope('public'),
            ]);

        return $account;
    }

    public function show($id)
    {
        return Account::query()
            ->whereKey($id)
            ->withBalancies()
            ->where('accounts.user_id', auth()->id())
            ->with('transactions')
            ->first();
    }

    public function delete(Request $request, Account $account)
    {
        $transactionsCount = $account->loadCount('transactions')->transactions_count;

        if ($transactionsCount) {
            $this->validate($request, [
                'to_account_id' => [
                    'required',
                    function (string $attribute, mixed $value, Closure $fail) use ($account) {
                        $toAccount = Account::query()
                            ->whereKey($value)
                            ->first();

                        if (!$toAccount) {
                            $fail("The $attribute not exists.");

                            return;
                        }

                        if ($toAccount->id == $account->id) {
                            $fail('Cannot move to the same account.');

                            return;
                        }

                        if ($toAccount->currency_id != $account->currency_id) {
                            $fail('Cannot move to account with different currency.');
                        }
                    },
                ],
            ]);
        }

        try {
            DB::beginTransaction();

            $account->transactions()
                ->update([
                    'account_id' => $request->to_account_id,
                ]);

            $account->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return response()->noContent();
    }

    public function pin(SettingsService $settingsService, int $accountId): Response
    {
        $userId = auth()->id();

        $settingsService->updateArrayKey('pinnedAccounts', $accountId, $userId);

        return response()->noContent();
    }

    public function viewAccountTypes(): JsonResponse
    {
        $user = auth()->user();

        $accountTypes = AccountType::query()
            ->where('user_id', $user->id)
            ->get();

        return response()->json($accountTypes);
    }

    public function saveAccountType(Request $request, ?AccountType $accountType = null): JsonResponse
    {
        $this->validate($request, [
            'name' => 'required|exists:currencies,id',
        ]);

        $accountType ??= new AccountType();

        $accountType->fill($request->only(['name']));

        $accountType->save();

        return response()->json($accountType);
    }
}
