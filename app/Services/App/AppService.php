<?php

namespace App\Services\App;

use App\Traits\HasInstanceGetter;
use Exception;
use Illuminate\Support\Facades\Storage;

class AppService
{
    use HasInstanceGetter;

    public function info(): array
    {
        return [
            'services' => $this->getServices(),
            'metadata' => [
                ...collect(config('metadata'))->mapWithKeys(
                    fn ($value, $key) => [str($key)->camel()->toString() => $value]
                ),
            ],
        ];
    }

    public function getServices(): array
    {
        $watchers = [
            'github.client_id',
            'google.client_id',
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

    /** @throws Exception */
    public function downloadDatabase(): string
    {
        $relativePath = MySQLDumperService::getInstance()
            ->download();

        return Storage::disk('public')->url($relativePath);
    }
}
