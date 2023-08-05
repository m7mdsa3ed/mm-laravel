<?php

namespace App\Services\Transactions;

use App\Models\Transaction;
use App\Services\Transactions\DTOs\TransactionData;
use App\Traits\HasInstanceGetter;

class TransactionMutationService
{
    use HasInstanceGetter;

    public function save(TransactionData $transactionData): Transaction
    {
        $transaction = $transactionData->transaction ?? new Transaction();

        $transaction->fill([
            'action' => $transactionData->action,
            'action_type' => $transactionData->action_type,
            'amount' => $transactionData->amount,
            'user_id' => $transactionData->user_id,
            'account_id' => $transactionData->account_id,
            'category_id' => $transactionData->category_id,
            'created_at' => $transactionData->created_at,
            'description' => $transactionData->description,
            'batch_id' => $transactionData->batch_id,
        ]);

        $transaction->save();

        return $transaction;
    }

    public function saveTags(Transaction $transaction, array $tagIds): Transaction
    {
        $transaction->tags()->sync($tagIds);

        return $transaction;
    }
}
