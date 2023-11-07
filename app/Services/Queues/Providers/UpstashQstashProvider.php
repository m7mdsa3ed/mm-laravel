<?php

namespace App\Services\Queues\Providers;

use App\Services\Queues\Contracts\Provider;
use Illuminate\Support\Facades\Http;

class UpstashQstashProvider implements Provider
{
    public function send(array $payload): bool
    {
        $baseUrl = config('queue.third_party_connections.upstash_qstash.url');

        $callbackEndpoint = config('queue.third_party_callback_endpoint');

        $url = $baseUrl . $callbackEndpoint;

        $headers = [
            'Authorization' => 'Bearer ' . config('queue.third_party_connections.upstash_qstash.token'),
            'Content-Type' => 'application/json',
        ];

        $response = Http::withHeaders($headers)->post($url, $payload);

        return $response->successful();
    }
}
