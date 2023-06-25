<?php

namespace App\Console\Commands;

use App\Actions\UpdateCurrencyRatesBulk;
use Illuminate\Console\Command;

class UpdateCurrencyRatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updateCurrencyRates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Currency Rates Command.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        dispatchAction(app(UpdateCurrencyRatesBulk::class));

        return self::SUCCESS;
    }
}
