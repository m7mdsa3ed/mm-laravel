<?php

namespace App\Providers;

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
}
