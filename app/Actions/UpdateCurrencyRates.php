<?php

namespace App\Actions;

use App\ThirdParty\XeScrapping;
use Illuminate\Support\Facades\Http;

class UpdateCurrencyRates extends Action
{
    public function __construct(
        private readonly array $args = [],
    ) {
    }

    public function execute(): mixed
    {
        $enabled = settings('upstreamCurrencyRates');

        if (!$enabled) {
            return false;
        }

        $validator = $this->validate($this->args, [
            'From' => 'required|string',
            'To' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        $xeScrapping = new XeScrapping();

        $request = $xeScrapping->getRequest($this->args);

        return Http::execute($request);
    }
}
