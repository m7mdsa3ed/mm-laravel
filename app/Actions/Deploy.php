<?php

namespace App\Actions;

use Illuminate\Support\Facades\Artisan;

class Deploy extends Action
{
    public array $results;

    public function execute(): mixed
    {
        $commands = [
            'migrate',
            'operations:process',
            'queue:restart',
        ];

        $results = [];

        foreach ($commands as $command) {
            Artisan::call($command);

            $output = Artisan::output();

            $output = str($output)
                ->replace("\n", '')
                ->trim();

            $results[$command] = $output;
        }

        $this->results = $results;
    }
}
