<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountsController extends Controller
{
    public function viewAny()
    {
        return Account::all();
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

    public function delete(Account $account)
    {
    }
}
