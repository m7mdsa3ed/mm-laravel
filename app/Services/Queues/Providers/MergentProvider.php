<?php

namespace App\Services\Queues\Providers;

use App\Services\Queues\Contracts\Provider;
use Illuminate\Support\Facades\Http;

class MergentProvider implements Provider
{
    public function send(array $payload): bool
    {
        $baseURL = config('queue.third_party_connections.mergent.url');

        $headers = [
            'Authorization' => 'Bearer ' . config('queue.third_party_connections.mergent.token'),
        ];

        $body = [
            'request' => [
                'url' => config('queue.third_party_callback_endpoint'),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Requested-With' => 'XMLHttpRequest',
                    'X-Signature' => config('queue.third_party_connections.mergent.signature'),
                ],
                'body' => json_encode($payload),
            ],
        ];

        $response = Http::withHeaders($headers)->post($baseURL, $body);

        return !($response->failed());
    }
}
