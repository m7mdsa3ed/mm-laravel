<?php

namespace App\Services\Transactions;

use App\Events\TransactionSaved;
use App\Models\Transaction;
use App\Services\Transactions\DTOs\TransactionData;
use App\Traits\HasInstanceGetter;

class TransactionMutationService
{
    use HasInstanceGetter;

    public function save(TransactionData $transactionData): Transaction
    {
        $transaction = $transactionData->transaction ?? new Transaction();

        $new = $transactionData->isNew();

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

        $changes = $new ? $transaction->toArray() : $transaction->getChanges();

        TransactionSaved::dispatch($transaction, $changes);

        return $transaction;
    }

    public function saveMany(TransactionData ...$data): void
    {
        // TODO - batch insert
        foreach ($data as $transactionData) {
            $this->save($transactionData);
        }
    }

    public function saveTags(Transaction $transaction, array $tagIds): Transaction
    {
        $transaction->tags()->sync($tagIds);

        return $transaction;
    }
}
