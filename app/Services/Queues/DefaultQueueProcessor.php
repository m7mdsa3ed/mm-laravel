<?php

namespace App\Services\Queues;

use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\DatabaseJob;
use Illuminate\Support\Facades\DB;
use Throwable;

class DefaultQueueProcessor
{
    public function handle(array $payload, string $connectionName): bool
    {
        try {
            $this->createJobRecord($connectionName, $payload)
                ->fire();

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function createJobRecord(string $connectionName, array $payload): JobContract
    {
        return match ($connectionName) {
            'database' => $this->createDatabaseJob($payload),
        };
    }

    private function createDatabaseJob(array $payload): JobContract
    {
        $jobRecord = DB::table('jobs')
            ->whereJsonContains('payload', ['uuid' => $payload['uuid']])
            ->first();

        return app(DatabaseJob::class, [
            'job' => $jobRecord,
            'connectionName' => 'database',
            'queue' => $payload['queue'] ?? 'default',
            'database' => app('queue')->connection('database'),
        ]);
    }
}
