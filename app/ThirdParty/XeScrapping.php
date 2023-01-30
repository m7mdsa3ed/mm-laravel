<?php

namespace App\ThirdParty;

use App\Models\Currency;
use App\Models\CurrencyRate;
use Closure;
use DOMDocument;
use DOMXPath;

class XeScrapping
{
    private $baseUrl = 'https://www.xe.com/currencyconverter/convert/';

    public function getRequest($args)
    {
        $args = $this->validateParams($args);

        return [
            'method' => 'get',
            'endpoint' => $this->baseUrl,
            'data' => $args,
            'parser' => Closure::fromCallable([$this, 'responseParser']),
            'listener' => Closure::fromCallable([$this, 'responseListener']),
        ];
    }

    private function validateParams($params)
    {
        $args = [];

        foreach ($params as $key => $value) {
            $key = ucfirst($key);

            $args[$key] = is_string($value) ? strtoupper($value) : $value;
        }

        return $args;
    }

    protected function responseParser($response, $requestData)
    {
        $data = $this->parseHtml($response->body());

        return [
            'from' => $requestData['From'],
            'to' => $requestData['To'],
            'rate' => filter_var($data['rate'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) * 1,
        ];
    }

    private function parseHtml($html)
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

    protected function responseListener($response, $requestData)
    {
        $from = $response['from'];

        $to = $response['to'];

        $rate = $response['rate'];

        $fromCurrency = Currency::updateOrCreate(['name' => $from]);

        $toCurrency = Currency::updateOrCreate(['name' => $to]);

        CurrencyRate::updateOrCreate([
            'from_currency_id' => $fromCurrency->id,
            'to_currency_id' => $toCurrency->id,
        ], ['rate' => $rate]);
    }
}
