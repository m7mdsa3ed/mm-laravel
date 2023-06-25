<?php

namespace App\Actions;

use App\Models\Currency;
use App\Services\Currencies\CurrencyRateService;

class UpdateCurrencyRatesBulk extends Action
{
    public function __construct(
        private readonly array $transformations = []
    ) {
    }

    public function execute(): bool
    {
        if (!settings('upstreamCurrencyRates')) {
            return false;
        }

        if (!count($this->transformations)) {
            $currencies = Currency::getSlugs()
                ->toArray();

            $currencies = $this->removeExcludedCurrencies($currencies);

            $transformations = Currency::getTransformations($currencies);
        } else {
            $transformations = $this->transformations;
        }

        $rates = CurrencyRateService::getInstance()->rates($transformations);

        foreach ($rates as $rate) {
            (new Currency())->onXeScrappingResponse($rate);
        }

        return true;
    }

    private function removeExcludedCurrencies(array $currencies): array
    {
        $excludedCurrencyIds = settings('upstreamCurrencyRatesExcludedIds');

        if ($excludedCurrencyIds) {
            $currencies = array_filter($currencies, function ($currencyId) use ($excludedCurrencyIds) {
                return !in_array($currencyId, $excludedCurrencyIds);
            }, ARRAY_FILTER_USE_KEY);
        }

        return $currencies;
    }
}
