<?php

namespace App\Http\Controllers;

use App\Actions\UpdateCurrencyRates;
use App\Enums\ActionEnum;
use App\Enums\ActionTypeEnum;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Nette\Schema\ValidationException;
use Throwable;

class TransactionsController extends Controller
{
    public function viewAny(Request $request)
    {
        $transactions = Transaction::query()
            ->with([
                'category',
                'account.currency',
                'tags',
            ])
            ->where('user_id', Auth::id())
            ->orderByRaw('created_at desc, id desc')
            ->filter([
                'category_id' => $request->category_id,
                'account_id' => $request->account_id,
                'tags' => $request->tag_id,
                'dates' => [$request->date_from, $request->date_to],
                'period' => $request->period,
            ])
            ->simplePaginate();

        $transactions->append('action_type_as_string');

        return $transactions;
    }

    public function save(Request $request, Transaction $transaction = null)
    {
        $transaction ??= new Transaction();

        $this->validate($request, [
            'action_type' => 'sometimes|required',
            'amount' => 'sometimes|required',
            'account_id' => 'sometimes|required',
            'tag_ids' => 'nullable|array',
        ]);

        $transaction->user()->associate(Auth::id());

        $fields = $request->only([
            'action',
            'action_type',
            'amount',
            'batch_id',
            'account_id',
            'category_id',
            'user_id',
            'created_at',
            'description',
        ]);

        $transaction->fill($fields);

        $transaction->save();

        $transaction->tags()->sync($request->tag_ids);

        $transaction->append('action_type_as_string');

        return $transaction->load('category', 'account', 'tags');
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
            ]);

            $toTransaction = Transaction::create([
                'action' => ActionEnum::IN(),
                'action_type' => ActionTypeEnum::MOVE(),
                'user_id' => Auth::id(),
                'account_id' => $request->to,
                'amount' => $toAmount,
                'is_public' => 1,
                'description' => $description,
            ]);

            if ($differentCurrency) {
                dispatch(
                    fn () => $this->calculateMovingFees(
                        $fromAmount,
                        $fromTransaction->account,
                        $toAmount,
                        $toTransaction->account,
                        Auth::id(),
                    )
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
        int $userId,
    ): void {
        dispatchAction(
            new UpdateCurrencyRates([
                'From' => $fromAccount->currency->name,
                'To' => $toAccount->currency->name,
            ])
        );

        $officialRate = null; // Passed By Reference

        $officialFromAmount = $toAccount->currency->convertMoney($toAmount, $fromAccount->currency, $officialRate);

        $movingFees = $fromAmount - $officialFromAmount;

        if ($movingFees == 0) {
            return;
        }

        $moveActionType = $movingFees < 0 ? 'income' : 'outcome';

        Transaction::create([
            'action' => $moveActionType == 'income' ? ActionEnum::IN() : ActionEnum::OUT(),
            'action_type' => $moveActionType == 'income' ? ActionTypeEnum::INCOME() : ActionTypeEnum::OUTCOME(),
            'user_id' => $userId,
            'account_id' => $fromAccount->id, // TODO should be from the main account (user's settings) and should consider the currency
            'amount' => abs($movingFees),
            'is_public' => 1,
            'description' => implode('\\n', [
                'Official Rate ' . $officialRate,
                'Move Rate ' . $fromAmount / $toAmount,
                'Moving Amount ' . $fromAmount,
                'To Amount ' . $toAmount,
            ]),
        ]);
    }
}
