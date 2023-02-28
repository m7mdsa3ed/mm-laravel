<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AccountsController extends Controller
{
    public function viewAny()
    {
        return Account::query()
            ->where('accounts.user_id', auth()->id())
            ->withBalancies()
            ->withcount(['transactions' => fn ($query) => $query->withoutGlobalScope('public')])
            ->with('currency')
            ->get();
    }

    /** @throws ValidationException */
    public function save(Request $request, Account $account = null)
    {
        $account ??= new Account();

        if ($account->id) {
            $validators = [
                'name' => 'required|unique:accounts,name,' . $account->id,
            ];
        }

        $this->validate($request, array_merge($validators ?? [], [
            'currency_id' => 'required|exists:currencies,id',
        ]));

        $data = $request->only([
            'name',
            'currency_id',
            'type_id',
        ]);

        $account->fill($data);

        $account->user()->associate(auth()->user());

        $account->save();

        $account->load('currency', 'user');

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

    public function delete(Account $account)
    {
        return response()->json([
            'message' => 'Account deleting is work in progress',
        ], 400);

        /**
         * TODO
         * 1. Move all transactions to another account
         * 2. Convert money to target account currency
         *   2.1. Request has to_amount => No need to convert
         * 3. Delete the account
         */
    }
}
