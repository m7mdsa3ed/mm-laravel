<?php

namespace App\Services\Currencies;

use App\Services\Currencies\Providers\Xe;
use App\Traits\HasInitializer;

class CurrencyRateService
{
    use HasInitializer;

    public function __construct(
        private readonly Xe $xe
    ) {

    }

    public function rates(array $transformations): array
    {
        return cache()->remember('xeCurrencyRates', now()->addMinutes(15), function () use ($transformations) {
            return $this->xe->getRates($transformations);
        });
    }
}
