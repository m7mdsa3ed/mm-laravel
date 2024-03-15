<?php

namespace App\Queries;

use App\Enums\ActionTypeEnum;
use App\Models\Contact;
use App\Models\TransactionContact;
use Illuminate\Database\Eloquent\Collection;
use DB;

class GetAllContactsQuery
{
    public static function get(int $userId, ?object $filters = null): mixed
    {
        return Contact::query()
            ->leftJoinSub(
                TransactionContact::query()
                    ->select('contact_id', 'transaction_id')
                    ->groupBy('transaction_id', 'contact_id'),
                'transaction_contacts',
                function ($join) {
                    $join->on('contacts.id', '=', 'transaction_contacts.contact_id');
                }
            )
            ->leftJoin('transactions', fn($join) => $join
                ->on('transactions.id', '=', 'transaction_contacts.transaction_id')
                ->whereIn('transactions.action_type', [
                    ActionTypeEnum::DEBIT,
                    ActionTypeEnum::LOAN,
                ])
            )
            ->where('contacts.user_id', $userId)
            ->when($filters->contactId ?? false, fn ($q, $cId) => $q->where('contacts.id', $cId))
            ->groupBy('contacts.id')
            ->select(...array_map(fn ($sql) => DB::raw($sql), [
                'contacts.*',
                'SUM(IFNULL(IF(transactions.action = 1, transactions.amount, -transactions.amount), 0)) AS balance_amount',
                'SUM(IF(transactions.action_type IN (4), IF(transactions.action = 1, transactions.amount, -transactions.amount), 0)) * -1 AS loan_amount',
                'SUM(IF(transactions.action_type IN (5), IF(transactions.action = 1, transactions.amount, -transactions.amount), 0)) * -1 AS debit_amount',
                'SUM(IF(transactions.action_type IN (6), IF(transactions.action = 1, transactions.amount, -transactions.amount), 0)) * -1 AS held_amount',
            ]))
            ->get();
    }
}
