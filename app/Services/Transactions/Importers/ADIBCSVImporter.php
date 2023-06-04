<?php

namespace App\Services\Transactions\Importers;

use App\Enums\ActionEnum;
use App\Enums\ActionTypeEnum;
use App\Imports\Importable;
use App\Models\Transaction;
use App\Services\Transactions\Contracts\TransactionsImporter;
use Carbon\Carbon;

class ADIBCSVImporter implements TransactionsImporter
{
    public function import(string $filePath, array $args = []): void
    {
        $importable = new Importable();

        $data = $importable->toArray($filePath);

        $transactions = $this->getTransactions($data);

        $transactions = array_map(function ($transaction) use ($args) {
            $amount = (float) filter_var(
                $transaction['Credit'] ?? $transaction['Debit'] ?? 0,
                FILTER_SANITIZE_NUMBER_FLOAT,
                FILTER_FLAG_ALLOW_FRACTION
            );

            $isCredit = $transaction['Credit'] ?? false;

            $action = $isCredit ? ActionEnum::IN->value : ActionEnum::OUT->value;

            $actionType = $isCredit ? ActionTypeEnum::INCOME->value : ActionTypeEnum::OUTCOME->value;

            return [
                'action' => $action,
                'action_type' => $actionType,
                'amount' => $amount,
                'description' => $transaction['Transaction Narrative'] ?? null,
                'account_id' => $args['accountId'],
                'user_id' => $args['userId'],
                'created_at' => Carbon::createFromFormat('d/m/Y', $transaction['Transaction Date'])->format('Y-m-d'),
            ];
        }, $transactions);

        Transaction::query()
            ->upsert($transactions, [
                'action',
                'amount',
                'account_id',
                'user_id',
            ]);
    }

    private function getTransactions(array $data): array
    {
        $transactionsIndex = null;

        foreach ($data[0] as $index => $row) {
            if ($row[0] === 'Transaction Date') {
                $transactionsIndex = $index;

                break;
            }
        }

        $headers = $data[0][$transactionsIndex];

        $transactions = [];

        foreach ($data[0] as $index => $row) {
            if ($index <= $transactionsIndex) {
                continue;
            }

            $transaction = array_combine($headers, $row);

            if ($transaction['Transaction Date'] === null) {
                continue;
            }

            $transactions[] = $transaction;
        }

        return $transactions;
    }
}
