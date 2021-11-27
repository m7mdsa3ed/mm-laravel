<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
                'dates'         => [$request->date_from, $request->date_to]
            ])
            ->simplePaginate();
    }

    public function save(Request $request, Transaction $transaction = null)
    {

        $transaction = $transaction ?? new Transaction;

        $this->validate($request, [
            'type'          => 'required',
            'amount'        => 'required',
            'account_id'    => 'required',
        ]);

        $transaction->user_id = Auth::id();

        $transaction
            ->fill($request->only([
                'type',
                'amount',
                'account_id',
                'category_id',
                'user_id',
                'created_at'
            ]));

        $transaction->description = $request->description;
        $transaction->save();

        return $transaction->load('category', 'account');
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
        ]);

        DB::transaction(function () use ($request) {
            Transaction::create(['user_id' => Auth::id(), 'account_id' => $request->from, 'amount' => $request->amount, 'type' => 2, 'is_public' => 0]);
            Transaction::create(['user_id' => Auth::id(), 'account_id' => $request->to, 'amount' => $request->amount, 'type' => 1, 'is_public' => 0]);
        });

        return [
            'message' => 'success'
        ];
    }
}
