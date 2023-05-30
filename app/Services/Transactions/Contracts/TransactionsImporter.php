<?php

namespace App\Services\Transactions\Contracts;

interface TransactionsImporter
{
    public function import(string $fileContent);
}
