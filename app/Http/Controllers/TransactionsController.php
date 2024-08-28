<?php

namespace App\Http\Controllers;

use App\Actions\UpdateCurrencyRatesBulk;
use App\Enums\ActionEnum;
use App\Enums\ActionTypeEnum;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\CurrencyTransferFeesNotification;
use App\Services\Transactions\DTOs\TransactionData;
use App\Services\Transactions\TransactionMutationService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Nette\Schema\ValidationException;
use Throwable;

class TransactionsController extends Controller
{
    public function viewAny(Request $request)
    {
        $transactions = $this->getTransactions($request);

        $transactions = $transactions->simplePaginate();

        $transactions->append('action_type_as_string');

        return $transactions;
    }

    /** @throws \Illuminate\Validation\ValidationException */
    public function save(
        Request $request,
        TransactionMutationService $transactionService,
        Transaction $transaction = null
    ): JsonResponse {
        $data = TransactionData::formRequest($request, [
            'transaction' => $transaction,
        ]);

        $transaction = $transactionService->save($data);

        $transaction->append('action_type_as_string');

        $transaction->loadMissing([
            'tags',
            'category',
            'account.currency',
            'contact',
        ]);

        return response()->json($transaction);
    }

    public function delete(Transaction $transaction)
    {
        $transaction->delete();

        return response()->noContent();
    }

    public function moveMoney(Request $request)
    {
        $this->validate($request, [
            'from' => 'required|numeric',
            'to' => 'required|numeric',
            'amount' => 'required|numeric',
            'toAmount' => 'sometimes|numeric',
        ]);

        $differentCurrency = $request->toAmount ?? false;

        $fromAmount = $request->amount;

        $toAmount = $request->toAmount ?? $fromAmount;

        $fromAccount = Account::withBalancies()->whereKey($request->from)->first();

        if ($fromAccount->balance < $fromAmount) {
            throw new ValidationException('Cannot move more than ' . $fromAccount->balance);
        }

        $description = $request->description;

        try {
            DB::beginTransaction();

            $fromTransaction = Transaction::create([
                'action' => ActionEnum::OUT(),
                'action_type' => ActionTypeEnum::MOVE(),
                'user_id' => Auth::id(),
                'account_id' => $request->from,
                'amount' => $fromAmount,
                'is_public' => 1,
                'description' => $description,
                'created_at' => $request->date ?? now(),
                'updated_at' => $request->date ?? now(),
            ]);

            $toTransaction = Transaction::create([
                'action' => ActionEnum::IN(),
                'action_type' => ActionTypeEnum::MOVE(),
                'user_id' => Auth::id(),
                'account_id' => $request->to,
                'amount' => $toAmount,
                'is_public' => 1,
                'description' => $description,
                'created_at' => $request->date ?? now(),
                'updated_at' => $request->date ?? now(),
            ]);

            if ($differentCurrency) {
                $this->calculateMovingFees(
                    $fromAmount,
                    $fromTransaction->account,
                    $toAmount,
                    $toTransaction->account,
                    $request->user(),
                );
            }

            DB::commit();
        } catch (Throwable $th) {
            DB::rollBack();

            throw $th;
        }

        return [
            'message' => 'success',
        ];
    }

    private function calculateMovingFees(
        float $fromAmount,
        Account $fromAccount,
        float $toAmount,
        Account $toAccount,
        User $user,
    ): void {
        dispatchAction(
            new UpdateCurrencyRatesBulk([
                [
                    'From' => $fromAccount->currency->name,
                    'To' => $toAccount->currency->name,
                ],
            ])
        );

        $officialRate = null; // Passed By Reference

        $officialFromAmount = $toAccount->currency->convertMoney($toAmount, $fromAccount->currency, $officialRate);

        $movingFees = $fromAmount - $officialFromAmount;

        if ($movingFees == 0) {
            return;
        }

        $user->notify(
            new CurrencyTransferFeesNotification([
                'fees_amount' => abs($movingFees),
                'official_rate' => $officialRate,
                'rate' => $fromAmount / $toAmount,
                'from_amount' => $fromAmount,
                'from_currency' => $fromAccount->currency->name,
                'to_amount' => $toAmount,
                'to_currency' => $toAccount->currency->name,
            ])
        );
    }

    /** @throws \Illuminate\Validation\ValidationException */
    public function import(Request $request): JsonResponse
    {
        $this->validate($request, [
            'file' => 'required|file',
            'type' => 'required|string',
        ]);

        $file = $request->file('file');

        $type = $request->type;

        try {
            TransactionMutationService::getInstance()
                ->import($type, $file->getContent());

            return response()->json([
                'message' => 'success',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function getTransactions(Request $request)
    {
        return Transaction::query()
            ->addSelect([
                'transactions.*',
                DB::raw('(
                    SELECT SUM(if(action = 1, ta.amount, -ta.amount))
                    FROM transactions ta
                    WHERE ta.account_id = transactions.account_id
                    AND ta.created_at <= transactions.created_at) AS balance
                '),
            ])
            ->with([
                'category',
                'account.currency',
                'tags',
                'contact',
            ])
            ->where('transactions.user_id', Auth::id())
            ->orderByRaw('transactions.created_at desc, transactions.id desc')
            ->filter([
                'category_id' => $request->category_id,
                'account_id' => $request->account_id,
                'tags' => $request->tag_id,
                'dates' => [$request->date_from, $request->date_to],
                'period' => $request->period,
                'description' => $request->description,
            ])
            ->when(
                $request->contact_id,
                fn ($query, $id) => $query->whereHas('contact', fn ($query) => $query->where('contact_id', $id))
            );
    }

    public function getDescriptionSuggestions(Request $request): JsonResponse
    {
        $request->validate([
            'category_id' => 'numeric|nullable',
        ]);

        $categoryId = $request->category_id;

        $keywords = Transaction::query()
            ->where('category_id', $categoryId)
            ->groupBy('description')
            ->orderByRaw('count(*) desc')
            ->select('description')
            ->whereNotNull('description')
            ->limit(10)
            ->pluck('description');

        return response()->json($keywords);
    }
}
