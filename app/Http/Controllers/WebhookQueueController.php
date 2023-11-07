<?php

namespace App\Http\Controllers;

use App\Services\Queues\QueueService;
use Illuminate\Http\Request;

class WebhookQueueController extends Controller
{
    public function handle(Request $request, QueueService $queueService)
    {
        $body = $request->all();

        $payload = $body['payload'];

        $connectionName = $body['connectionName'];

        $queueService->process($payload, $connectionName);
    }
}
