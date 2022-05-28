<?php

namespace App\Console\Schedules;

class UpdateCurrencyRates
{
    public function __invoke()
    {
        $args = [
            'From' => 'EGP',
            'To' => 'USD',
            'Amount' => 1,
        ];

        $action = new \App\Actions\UpdateCurrencyRates;

        $action->execute($args);
    }
}
