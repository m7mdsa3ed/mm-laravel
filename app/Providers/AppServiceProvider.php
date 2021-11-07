<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
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
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //

        // ! TEMP
        if (Schema::hasTable('users')) {
            Auth::loginUsingId(1);
        }

        // Force HTTPS for pagination
        $this->app['request']->server->set('HTTPS', 'on');
    }
}
