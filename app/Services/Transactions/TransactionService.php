<?php

namespace App\Services\Transactions;

use App\Services\Transactions\Contracts\TransactionsImporter;
use App\Services\Transactions\Importers\ADIBCSVImporter;
use App\Traits\HasInstanceGetter;
use Exception;

class TransactionService
{
    use HasInstanceGetter;

    /** @throws Exception */
    public function import(string $type, string $filePath)
    {
        $importer = $this->getImporter($type);

        if (!$importer) {
            throw new Exception('Importer not found');
        }

        return $importer->import($filePath);
    }

    private function getImporter(string $type): ?TransactionsImporter
    {
        $importer = match ($type) {
            'adib-csv' => ADIBCSVImporter::class,
            default => null,
        };

        if ($importer) {
            return app($importer);
        }

        return null;
    }
}
