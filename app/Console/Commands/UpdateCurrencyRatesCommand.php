<?php

namespace App\Console\Commands;

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
        $args = [
            [
                'From' => 'EGP',
                'To' => 'USD',
                'Amount' => 1,
            ],
            [
                'From' => 'EGP',
                'To' => 'XAU',
                'Amount' => 1,
            ],
        ];

        foreach ($args as $arg) {
            $action = new \App\Actions\UpdateCurrencyRates($arg);

            $action->execute();
        }

        return Command::SUCCESS;
    }
}
