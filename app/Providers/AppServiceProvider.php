<?php

namespace App\Providers;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Force HTTPS for pagination
        $this->app['request']->server->set('HTTPS', 'on');

        $this->setLocalConfigs();

        $this->setQueueListeners();
    }

    private function setLocalConfigs(): void
    {
        $isLocal = !app()->environment('production');

        if ($isLocal) {
            if (config('app.settings.local.forceLoginId')) {
                auth()->loginUsingId(config('app.settings.local.forceLoginId'));
            }

            usleep(config('app.settings.local.apiSleepTimeInMilliseconds'));
        }
    }

    private function setQueueListeners(): void
    {
        Queue::createPayloadUsing(function (string $connectionName, mixed $queueName, array $job) {
            if (config('queue.third_party_default') && config('queue.default') != 'sync') {
                $queueService = app(\App\Services\Queues\QueueService::class);

                $queueService->dispatch([
                    ...$job,
                    'data' => [
                        ...$job['data'],
                        'commandName' => serialize($job['data']['commandName']),
                        'command' => serialize($job['data']['command']),
                    ],
                ]);
            }

            return $job;
        });
    }
}
