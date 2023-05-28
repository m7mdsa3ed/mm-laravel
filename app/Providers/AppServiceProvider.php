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

        if (env('FORCE_LOGIN_ID') && !app()->environment('production')) {
            auth()->loginUsingId(env('FORCE_LOGIN_ID'));
        }
    }
}
