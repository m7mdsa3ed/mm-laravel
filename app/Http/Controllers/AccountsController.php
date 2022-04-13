<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountsController extends Controller
{
    public function viewAny()
    {
        return Account::where('user_id', auth()->id())->selectBalance(auth()->user())
            ->withcount(['transactions' => fn ($query) => $query->withoutGlobalScope('public')])
            ->get();;
    }

    public function save(Request $request, Account $account = null)
    {
        $account = $account ?? new Account;

        if ($account->id) {
            $this->validate($request, [
                "name"  => 'required|unique:accounts,name,' . $account->id
            ]);
        }

        $account->fill($request->only('name'))
            ->save();

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
