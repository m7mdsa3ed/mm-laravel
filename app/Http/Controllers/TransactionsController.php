<?php

namespace App\Http\Controllers;

use App\Enums\ActionEnum;
use App\Enums\ActionTypeEnum;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionsController extends Controller
{
    public function viewAny(Request $request)
    {
        return Transaction::with('category', 'account')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->filter([
                'category_id'   => $request->category_id,
                'account_id'    => $request->account_id,
                'dates'         => [$request->date_from, $request->date_to],
                'period'        => $request->period,
            ])
            ->simplePaginate();
    }

    public function save(Request $request, Transaction $transaction = null)
    {
        $transaction = $transaction ?? new Transaction;

        $this->validate($request, [
            'action'        => 'sometimes|required',
            'action_type'   => 'sometimes|required',
            'amount'        => 'sometimes|required',
            'account_id'    => 'sometimes|required',
            'tags'          => 'nullable|array'
        ]);

        $transaction->user()->associate(Auth::id());

        $fields = $request->only([
            'action',
            'action_type',
            'amount',
            'account_id',
            'category_id',
            'user_id',
            'created_at',
            'description',
        ]);

        $transaction->fill($fields);

        $transaction->save();

        $transaction->tags()->sync($request->tags);

        return $transaction->load('category', 'account', 'tags');
    }

    public function delete(Transaction $transaction)
    {
        $transaction->delete();
    }

    public function moveMoney(Request $request)
    {
        $this->validate($request, [
            'from'      => 'required|numeric',
            'to'        => 'required|numeric',
            'amount'    => 'required|numeric',
            'toAmount'  => 'sometimes|numeric',
        ]);

        $fromAmount = $request->amount;

        $toAmount = $request->toAmount ?? $fromAmount;

        $isPublic = $fromAmount != $toAmount;

        $description = $request->description;

        try {
            DB::beginTransaction();

            Transaction::create([
                'action' => ActionEnum::OUT(),
                'action_type' => ActionTypeEnum::MOVE(),
                'user_id' => Auth::id(),
                'account_id' => $request->from,
                'amount' => $fromAmount,
                'is_public' => $isPublic,
                'description' => $description,
            ]);

            Transaction::create([
                'action' => ActionEnum::IN(),
                'action_type' => ActionTypeEnum::MOVE(),
                'user_id' => Auth::id(),
                'account_id' => $request->to,
                'amount' => $toAmount,
                'is_public' => $isPublic,
                'description' => $description,
            ]);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }

        return [
            'message' => 'success'
        ];
    }
}
