<?php

namespace App\Console;

use App\Services\Subscriptions\SubscriptionService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use TimoKoerber\LaravelOneTimeOperations\Commands\OneTimeOperationsProcessCommand;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        OneTimeOperationsProcessCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            /** @var SubscriptionService $subscriptionService */
            $subscriptionService = app(SubscriptionService::class);

            $subscriptionService->runSchedule();
        })->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
