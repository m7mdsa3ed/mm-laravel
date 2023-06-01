<?php

namespace App\Services\Transactions;

use App\Services\Transactions\Contracts\TransactionsImporter;
use App\Services\Transactions\Importers\XlsxImporter;
use App\Traits\HasInstanceGetter;
use Exception;

class TransactionService
{
    use HasInstanceGetter;

    /** @throws Exception */
    public function import(string $type, string $fileContent)
    {
        $importer = $this->getImporter($type);

        if (!$importer) {
            throw new Exception('Importer not found');
        }

        if (!$importer instanceof TransactionsImporter) {
            throw new Exception('Importer must implement TransactionsImporter');
        }

        return $importer->import($fileContent);
    }

    private function getImporter(string $type): string
    {
        return match ($type) {
            'xlsx' => XlsxImporter::class,
            default => null,
        };
    }
}
