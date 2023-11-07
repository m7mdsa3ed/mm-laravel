<?php

namespace App\Services\Queues;

use App\Services\Queues\Contracts\Provider;
use App\Services\Queues\Providers\MergentProvider;
use App\Services\Queues\Providers\UpstashQstashProvider;

class QueueService
{
    public function dispatch(array $payload): void
    {
        $provider = [
            'mergent' => MergentProvider::class,
            'upstash_qstash' => UpstashQstashProvider::class,
        ][config('queue.third_party_default')];

        /** @var Provider $mergentProvider */
        $provider = app($provider);

        $provider->send([
            'payload' => $payload,
            'connectionName' => config('queue.default'),
        ]);
    }

    public function process(array $payload, string $connectionName): void
    {
        /** @var DefaultQueueProcessor $processor */
        $processor = app(DefaultQueueProcessor::class);

        $processor->handle($payload, $connectionName);
    }
}
