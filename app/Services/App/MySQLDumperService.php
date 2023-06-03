<?php

namespace App\Services\App;

use App\Traits\HasInstanceGetter;
use Exception;
use Ifsnop\Mysqldump\Mysqldump;
use Illuminate\Support\Facades\Storage;
use PDO;

class MySQLDumperService
{
    use HasInstanceGetter;

    private array $mysqlConfigs = [];

    private array $dumperConfigs = [];

    private array $pdoConfigs = [];

    public function __construct()
    {
        $this->loadConfigs();
    }

    private function loadConfigs(): void
    {
        $this->mysqlConfigs = config('database.connections.mysql');

        $this->dumperConfigs = [
            'single-transaction' => false,
        ];

        if (env('MYSQL_ATTR_SSL_CA')) {
            $this->pdoConfigs = [
                ...$this->pdoConfigs,
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ];
        }
    }

    /** @throws Exception */
    public function download(): string
    {
        $outputPath = $this->getAbsoluteOutputFilePath();

        $this->cleanOldDumps();

        $this->createEmptyPlaceholder();

        $dumper = new Mysqldump(
            dsn: "mysql:host={$this->mysqlConfigs['host']};port={$this->mysqlConfigs['port']};dbname={$this->mysqlConfigs['database']}",
            user: $this->mysqlConfigs['username'],
            pass: $this->mysqlConfigs['password'],
            dumpSettings: $this->dumperConfigs,
            pdoSettings: $this->pdoConfigs
        );

        $dumper->start($outputPath);

        return $this->getRelativeOutputFilePath();
    }

    private function getOutputFileName(): string
    {
        return $this->mysqlConfigs['database'] . '-' . date('Y-m-d-H-i-s') . '.sql';
    }

    private function getRelativeOutputFilePath(): string
    {
        return 'dumps/' . $this->getOutputFileName();
    }

    private function getAbsoluteOutputFilePath(): string
    {
        return Storage::disk('public')->path($this->getRelativeOutputFilePath());
    }

    private function createEmptyPlaceholder(): void
    {
        Storage::disk('public')->put($this->getRelativeOutputFilePath(), '');
    }

    private function cleanOldDumps(): void
    {
        $files = Storage::disk('public')->files('dumps');

        foreach ($files as $file) {
            Storage::disk('public')->delete($file);
        }
    }
}
