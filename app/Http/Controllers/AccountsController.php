<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountsController extends Controller
{
    public function viewAny()
    {
        return Account::where('user_id', auth()->id())
            ->selectBalance(auth()->user())
            ->selectBalanceForCurrency(auth()->user(), 1)
            ->withcount(['transactions' => fn ($query) => $query->withoutGlobalScope('public')])
            ->with('currency')
            ->get();
    }

    public function save(Request $request, Account $account = null)
    {
        $account = $account ?? new Account;

        if ($account->id) {
            $validators = [
                "name"  => 'required|unique:accounts,name,' . $account->id
            ];
        }

        $this->validate($request, array_merge($validators ??= [], [
            'currency_id' => 'required|exists:currencies,id',
        ]));

        $data = $request->only(
            'name',
            'currency_id',
        );

        $account->fill($data);

        $account->user()->associate(auth()->user());

        $account->save();

        $account->load('currency', 'user');

        return $account;
    }

    public function show($id)
    {
        return Account::whereKey($id)
            ->selectBalance()
            ->with('transactions')
            ->first();
    }

    public function delete(Account $account)
    {
        $account->delete();
    }
}
