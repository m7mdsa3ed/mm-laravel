<?php

namespace App\Services\Currencies\Providers;

use App\Traits\HasInitializer;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;

class Xe
{
    use HasInitializer;

    private string $baseUrl = 'https://www.xe.com/currencyconverter/convert/';

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function validateParams(array $params): array
    {
        $args = [];

        foreach ($params as $key => $value) {
            $key = ucfirst($key);

            $args[$key] = is_string($value) ? strtoupper($value) : $value;
        }

        return $args;
    }

    public function responseParser($response, $requestData): array
    {
        $data = $this->parseHtml($response->body());

        if (!$data['rate']) {
            return [];
        }

        return [
            'from' => $requestData['From'],
            'to' => $requestData['To'],
            'rate' => filter_var($data['rate'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) * 1,
        ];
    }

    private function parseHtml($html): array
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();

        $dom->loadHTML($html);

        $xpath = new DOMXPath($dom);

        $nodes = $xpath->query('//p[@class="result__BigRate-sc-1bsijpp-1 iGrAod"]');

        if ($nodes->length > 0) {
            $nodeValue = $nodes->item(0)->nodeValue;

            $rate = explode(' ', $nodeValue)[0];
        }

        return [
            'rate' => $rate ?? null,
        ];
    }

    public function run(array $transformations): array
    {
        $responses = Http::retry(3)
            ->pool(function (Pool $pool) use ($transformations) {
                $requests = [];

                foreach ($transformations as $transformation) {
                    $requests[] = $pool->get($this->getBaseUrl(), $this->validateParams($transformation));
                }

                return $requests;
            });

        return collect($responses)
            ->map(function ($response, $index) use ($transformations) {
                $requestData = $transformations[$index];

                return $this->responseParser($response, $requestData);
            })
            ->filter()
            ->toArray();
    }
}
