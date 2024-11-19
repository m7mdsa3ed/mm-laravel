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

    private function getRatesFromHtml(string $html, string $currencySlug): array
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();

        $dom->loadHTML($html);

        $xpath = new DOMXPath($dom);

        $nodes = $xpath->query('//script[@id="__NEXT_DATA__"]');

        if ($nodes->length > 0) {
            $value = $nodes->item(0)->nodeValue;

            $data = json_decode($value, true);

            $rates = @(($data['props']['pageProps']['initialRatesData']['rates'] ?? []));

            $mainCurrencyRate = $rates[$currencySlug];

            return array_map(function ($to, $rate) use ($mainCurrencyRate, $currencySlug) {
                return [
                    'from' => $currencySlug,
                    'to' => $to,
                    'rate' => $rate / $mainCurrencyRate,
                ];
            }, array_keys($rates), $rates);
        }

        return [];
    }

    public function getRates(array $transformations): array
    {
        $responses = Http::pool(function (Pool $pool) use ($transformations) {
            $requests = [];

            foreach ($transformations as $transformation) {
                $requests[] = $pool
                    ->as($transformation['From'])
                    ->get($this->getBaseUrl(), $this->validateParams($transformation));
            }

            return $requests;
        });

        return collect($responses)
            ->map(function ($response, $from) {
                return $this->getRatesFromHtml($response->body(), $from);
            })
            ->flatten(1)
            ->unique()
            ->toArray();
    }
}
