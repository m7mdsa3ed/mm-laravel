<?php

namespace App\Listeners;

use App\Models\ApiResponse;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;

class HttpListener
{
    private $eventsMap = [
        'Illuminate\Http\Client\Events\RequestSending' => 'RequestSending',
        'Illuminate\Http\Client\Events\ResponseReceived' => 'ResponseReceived',
        'Illuminate\Http\Client\Events\ConnectionFailed' => 'ConnectionFailed',
    ];

    public function handle($event)
    {
        $eventName = get_class($event);

        $eventMethod = $this->eventsMap[$eventName] ?? null;

        if ($eventMethod && method_exists($this, $eventMethod)) {
            $request = $event->request;

            $response = $event->response ?? null;

            $this->$eventMethod($request, $response);
        }
    }

    private function RequestSending(Request $request)
    {
    }

    private function ResponseReceived(Request $request, Response $response)
    {
        ApiResponse::create([
            'response' => json_encode([
                'body' => $response->json(),
                'headers' => $response->headers(),
            ]),
        ]);
    }

    private function ConnectionFailed(Request $request)
    {
    }
}
