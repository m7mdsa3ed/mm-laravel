<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Concurrency extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:concurrency';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        sleep(2);

        info(json_encode([
            'success' => true,
            'message' => 'Command executed successfully.',
        ]));

        $this->output->write(json_encode([
            'success' => true,
            'message' => 'Command executed successfully.',
        ]));
    }
}
