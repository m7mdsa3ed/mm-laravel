<?php

namespace App\Services\Transactions;

use App\Enums\ActionEnum;
use App\Enums\ActionTypeEnum;
use App\Events\TransactionSaved;
use App\Models\Transaction;
use App\Models\TransactionContact;
use App\Services\Transactions\DTOs\TransactionData;
use App\Traits\HasInstanceGetter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

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

        $this->saveTags($transaction, $transactionData->tag_ids);

        if ($transactionData->contact_id) {
            $this->saveContact($transaction, $transactionData->contact_id);
        }

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

    public function saveContact(Transaction $transaction, int $contactId): Transaction
    {
        TransactionContact::query()
            ->updateOrCreate([
                'transaction_id' => $transaction->id,
                'contact_id' => $contactId,
            ]);

        return $transaction;
    }

    public function import(string $type, UploadedFile $file): void
    {
        $filePath = $file->getPath();

        $data = parseCSVWithHeadersAndMerge($filePath);

        $data = array_filter($data, fn($row) => !$row['Skip']);

        $data = loadRelations($data, [
            [
                'model' => new \App\Models\Category(),
                'modelKey' => 'name',
                'arrayKey' => 'CategoryId',
            ],
            [
                'model' => new \App\Models\Account(),
                'modelKey' => 'id',
                'arrayKey' => 'AccountId',
            ],
        ]);

        $batchId = now()->timestamp;

        $transactions = array_map(function ($row) use ($batchId) {
            $action = ($row['Amount'] > 0 ? ActionEnum::IN : ActionEnum::OUT)->value;

            $actionType = ($action == ActionEnum::IN->value ? ActionTypeEnum::INCOME : ActionTypeEnum::OUTCOME)->value;

            return new TransactionData(
                action: $action,
                action_type: $actionType,
                amount: abs($row['Amount']),
                user_id: auth()->id(),
                account_id: $row['account']['id'],
                category_id: $row['category']['id'] ?? null,
                created_at: Carbon::parse($row['Date']),
                description: $row['Description'] ?? null,
                batch_id: $batchId
            );
        }, $data);

        TransactionMutationService::getInstance()
            ->saveMany(...$transactions);
    }
}
