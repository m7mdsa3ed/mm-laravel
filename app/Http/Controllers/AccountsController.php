<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Queries\AccountSummaryQuery;
use App\Services\Accounts\AccountsService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

class AccountsController extends Controller
{
    public function viewAny(AccountsService $accountsService, Request $request)
    {
        $filters = $request->only([
            'name',
            'is_active',
        ]);

        return $accountsService->query()
            ->getAccounts(auth()->id(), $filters);
    }

    /** @throws ValidationException */
    public function save(AccountsService $accountsService, Request $request, Account $account = null): JsonResponse
    {
        $this->validate($request, [
            'currency_id' => 'required|exists:currencies,id',
        ]);

        $account = $accountsService->saveAccount($request, $account);

        $account = $account->newQuery()
            ->withBalancies()
            ->with([
                'currency',
                'user',
                'type',
            ])
            ->withCount([
                'transactions' => fn ($query) => $query->withoutGlobalScope('public'),
            ])
            ->whereKey($account->id)
            ->first();

        return response()->json($account);
    }

    public function show(AccountsService $accountsService, int $id)
    {
        return $accountsService->query()
            ->getAccount($id, auth()->id());
    }

    public function summary(Request $request, int $accountId)
    {
        $userId = auth()->id();

        $details = AccountSummaryQuery::get(
            $userId,
            $accountId,
            $request->date('from_date'),
            $request->date('to_date'),
        );

        return response()
            ->json($details);
    }

    /**
     * @param AccountsService $accountService
     * @param Request $request
     * @param Account $account
     * @return Response
     * @throws Throwable
     */
    public function delete(AccountsService $accountService, Request $request, Account $account): Response
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

        $accountService->deleteAccount($account, $request->to_account_id);

        return response()->noContent();
    }
}
