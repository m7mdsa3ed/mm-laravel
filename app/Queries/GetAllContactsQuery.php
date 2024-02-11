<?php

namespace App\Queries;

use App\Models\Contact;
use App\Models\TransactionContact;
use Illuminate\Database\Eloquent\Collection;
use DB;

class GetAllContactsQuery
{
    public static function get(int $userId): Collection
    {
        return Contact::query()
            ->joinSub(
                TransactionContact::query()
                    ->select('contact_id', 'transaction_id')
                    ->groupBy('transaction_id', 'contact_id'),
                'transaction_contacts',
                function ($join) {
                    $join->on('contacts.id', '=', 'transaction_contacts.contact_id');
                }
            )
            ->join('transactions', 'transaction_contacts.transaction_id', '=', 'transactions.id')
            ->where('contacts.user_id', $userId)
            ->groupBy('contacts.id')
            ->select(...array_map(fn ($sql) => DB::raw($sql), [
                'contacts.*',
                'SUM(IF(transactions.action = 1, transactions.amount, -transactions.amount)) AS balance_amount',
                'SUM(IF(transactions.action_type IN (4), IF(transactions.action = 1, transactions.amount, -transactions.amount), 0)) * -1 AS loan_amount',
                'SUM(IF(transactions.action_type IN (5), IF(transactions.action = 1, transactions.amount, -transactions.amount), 0)) * -1 AS debit_amount',
                'SUM(IF(transactions.action_type IN (6), IF(transactions.action = 1, transactions.amount, -transactions.amount), 0)) * -1 AS held_amount',
            ]))
            ->get();
    }
}
