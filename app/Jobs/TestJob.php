<?php

namespace App\Jobs;

use App\Mail\TestMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class TestJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** Create a new job instance. */
    public function __construct(
        private readonly string $email,
    ) {
    }

    /** Execute the job. */
    public function handle(): void
    {
        Mail::to($this->email)
            ->send(
                new TestMail(
                    message: 'This is a test message.',
                )
            );

        info('TestJob has been executed.');
    }
}
