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
        $transactions = Transaction::with('category', 'account', 'tags')
            ->where('user_id', Auth::id())
            ->orderByRaw("created_at desc, id desc")
            ->filter([
                'category_id'   => $request->category_id,
                'account_id'    => $request->account_id,
                'tags'          => $request->tag_id,
                'dates'         => [$request->date_from, $request->date_to],
                'period'        => $request->period,
            ])
            ->simplePaginate();

        $transactions->append('action_type_as_string');

        return $transactions;
    }

    public function save(Request $request, Transaction $transaction = null)
    {
        $transaction = $transaction ?? new Transaction;

        $this->validate($request, [
            'action_type'   => 'sometimes|required',
            'amount'        => 'sometimes|required',
            'account_id'    => 'sometimes|required',
            'tag_ids'       => 'nullable|array'
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

        $transaction->tags()->sync($request->tag_ids);

        $transaction->append('action_type_as_string');

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
