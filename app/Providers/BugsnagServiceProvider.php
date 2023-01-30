<?php

namespace App\Providers;

use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Illuminate\Support\ServiceProvider;

class BugsnagServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Bugsnag::registerCallback(function ($report) {
            $report->setMetaData($this->getMetaData());
        });

        Bugsnag::setAutoCaptureSessions(true);
    }

    private function getMetaData()
    {
        return [

        ];
    }
}
