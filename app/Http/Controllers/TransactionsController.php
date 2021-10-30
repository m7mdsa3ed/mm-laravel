<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionsController extends Controller
{
    public function viewAny()
    {
        return Transaction::with('category', 'account')->where('user_id', Auth::id())->orderBy('created_at', 'desc')->simplePaginate();
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
            ]));

        $transaction->description = $request->description;
        $transaction->save();

        return $transaction->load('category', 'account');
    }

    public function delete(Transaction $transaction)
    {
        $transaction->delete();
    }
}
