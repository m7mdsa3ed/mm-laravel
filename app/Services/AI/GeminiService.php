<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Throwable;

class GeminiService
{
    private string $apiKey;

    private string $baseUrl;

    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');

        $this->baseUrl = config('services.gemini.base_url');
    }

    public function generateContent(string $input, ?string $model = null): ?string
    {
        $this->setModel($model);

        $data = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'text' => $input,
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 1,
                'topK' => 64,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'text/plain',
            ],
        ];

        try {
            $response = Http::post($this->getModelUrl(), $data);

            return $response->json('candidates.0.content.parts.0.text');
        } catch (Throwable) {
            return null;
        }
    }

    private function getModelUrl(): string
    {
        $parts = [
            $this->baseUrl,
            'models',
            $this->model,
        ];

        return implode('/', $parts) . '?key=' . $this->apiKey;
    }

    private function setModel(?string $model): void
    {
        $this->model = match($model) {
            'pro' => 'gemini-1.5-pro:generateContent',
            default => 'gemini-1.5-flash:generateContent'
        };
    }
}
