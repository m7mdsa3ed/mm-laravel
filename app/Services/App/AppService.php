<?php

namespace App\Services\App;

use App\Enums\AccountType;
use App\Traits\HasInstanceGetter;
use Illuminate\Support\Facades\Storage;
use Spatie\DbDumper\Databases\MySql;

class AppService
{
    use HasInstanceGetter;

    public function info(): array
    {
        return [
            'accountTypes' => $this->getAccountTypes(),
            'services' => $this->getServices(),
            'metadata' => [
                ...collect(config('metadata'))->mapWithKeys(
                    fn ($value, $key) => [str($key)->camel()->toString() => $value]
                ),
            ],
        ];
    }

    public function getAccountTypes(): array
    {
        return collect(AccountType::cases())
            ->map(function ($case) {
                return [
                    'id' => $case->value,
                    'name' => AccountType::getName($case->value),
                ];
            })
            ->toArray();
    }

    public function getServices(): array
    {
        $watchers = [
            'github.client_id',
        ];

        $services = [];

        foreach ($watchers as $watcher) {
            $value = config('services.' . $watcher);

            if ($value) {
                $services[] = explode('.', $watcher)[0];
            }
        }

        return $services;
    }

    public function downloadDatabase(): string
    {
        $relativePath = 'database-dumps/' . config('database.connections.mysql.database') . '.sql';

        $absPath = Storage::disk('public')->path($relativePath);

        Storage::disk('public')->put($relativePath, '');

        MySql::create()
            ->setPort(config('database.connections.mysql.port'))
            ->setHost(config('database.connections.mysql.host'))
            ->setDbName(config('database.connections.mysql.database'))
            ->setUserName(config('database.connections.mysql.username'))
            ->setPassword(config('database.connections.mysql.password'))
            ->dumpToFile($absPath);

        return Storage::disk('public')->url($relativePath);
    }
}
