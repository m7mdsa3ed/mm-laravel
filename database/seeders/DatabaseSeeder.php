<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserAccount;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        $mainAccounts = ['Cash', 'Bank', 'Saving', 'Loans', 'Debits'];
        $accounts = [];

        foreach ($mainAccounts as $account) {
            $accounts[] = Account::updateOrCreate([ 'name' => $account, 'is_global' => 1 ]);
        }

        $user = User::updateOrCreate([
            'name'      => 'Mohamed',
            'email'     => 'm7md.sa3ed@hotmail.com',
            'password'  => bcrypt(123456)
        ]);

    }
}
