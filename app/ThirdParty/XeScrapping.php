<?php

namespace App\ThirdParty;

use App\Http\Requests\HttpRequest;
use App\Models\Currency;
use App\Traits\HasInitializer;
use DOMDocument;
use DOMXPath;

class XeScrapping
{
    use HasInitializer;

    private string $baseUrl = 'https://www.xe.com/currencyconverter/convert/';

    public function getRequest(array $args, array $listeners = [])
    {
        $args = $this->validateParams($args);

        return new HttpRequest(
            method: 'GET',
            url: $this->baseUrl,
            params: $args,
            formatter: $this->responseParser(...),
            listeners: [
                (new Currency())->XeScrappingListener(...),
                ...$listeners,
            ]
        );
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
}
