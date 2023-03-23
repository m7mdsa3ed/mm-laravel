<?php

namespace App\Console\Commands;

use App\Models\Currency;
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
    public function handle()
    {
        $currencies = Currency::getSlugs()->toArray();

        $transformations = Currency::getTransformationsFromCurrencies($currencies);

        foreach ($transformations as $transformation) {
            dispatchAction(new \App\Actions\UpdateCurrencyRates($transformation));
        }

        return Command::SUCCESS;
    }
}
