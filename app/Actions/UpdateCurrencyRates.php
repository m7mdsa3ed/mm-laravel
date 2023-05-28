<?php

namespace App\Actions;

use App\Models\Currency;
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

        $excludedCurrencies = Currency::query()
            ->whereIn('id', settings('upstreamCurrencyRatesExcludedIds') ?? []);

        $validator = $this->validate($this->args, [
            'From' => 'required|string|not_in:' . $excludedCurrencies->pluck('slug')->join(', '),
            'To' => 'required|string|not_in:' . $excludedCurrencies->pluck('slug')->join(', '),
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        $xeScrapping = new XeScrapping();

        $request = $xeScrapping->getRequest($this->args);

        return Http::execute($request);
    }
}
