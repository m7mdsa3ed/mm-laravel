<?php

namespace App\Actions;

use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Services\Currencies\CurrencyRateService;
use Illuminate\Support\Facades\DB;

class UpdateCurrencyRatesBulk extends Action
{
    public function __construct(
        private readonly array $transformations,
    ) {

    }

    public function execute(): bool
    {
        if (!settings('upstreamCurrencyRates')) {
            return false;
        }

        $transformations = $this->transformations;

        $transformations = collect($transformations)
            ->unique('From')
            ->toArray();

        $rates = CurrencyRateService::getInstance()->rates($transformations);

        $this->saveRates($rates);

        return true;
    }

    private function saveRates(array $rates): void
    {
        $rates = $this->removeExcludedCurrencies($rates);

        $rates = $this->createOppositeRates($rates);

        $rates = (new CurrencyRate())->ratesFormatting($rates);

        $rates = $this->createXauKarats($rates);

        $currencies = array_unique([
            ...array_column($rates, 'from'),
            ...array_column($rates, 'to'),
        ]);

        try {
            DB::beginTransaction();

            Currency::query()
                ->upsert(
                    array_map(fn ($currency) => [
                        'slug' => $currency,
                        'name' => $currency,
                    ], $currencies),
                    ['slug'],
                    ['name']
                );

            $currencies = Currency::query()
                ->pluck('id', 'slug');

            $data = array_map(function ($row) use ($currencies) {
                ['from' => $from, 'to' => $to, 'rate' => $rate] = $row;

                return [
                    'from_currency_id' => $currencies[$from],
                    'to_currency_id' => $currencies[$to],
                    'rate' => $rate,
                ];
            }, $rates);

            CurrencyRate::query()
                ->upsert($data, ['from_currency_id', 'to_currency_id'], ['rate']);

            DB::commit();

            return;
        } catch (Throwable) {
            DB::rollBack();

            return;
        }
    }

    private function createOppositeRates(array $rates): array
    {
        $data = [];

        foreach ($rates as $rate) {
            $data[] = $rate;

            $data[] = [
                'from' => $rate['to'],
                'to' => $rate['from'],
                'rate' => 1 / $rate['rate'],
            ];
        }

        return array_unique($data, SORT_REGULAR);
    }

    private function removeExcludedCurrencies(array $rates): array
    {
        $excludedCurrencyIds = settings('upstreamCurrencyRatesExcludedIds');

        $currencies = Currency::query()
            ->whereIn('id', $excludedCurrencyIds)
            ->whereHas('rates')
            ->pluck('slug');

        return array_filter($rates, function ($rate) use ($currencies) {
            return !in_array($rate['from'], $currencies->toArray()) || !in_array($rate['to'], $currencies->toArray());
        });
    }

    private function createXauKarats(array $rates): array
    {
        $xauRates = array_filter($rates, fn ($rate) => $rate['from'] === 'XAU');

        foreach ($xauRates as $row) {
            $karats = [24, 22, 21, 18, 14, 10];

            foreach ($karats as $k) {
                $rates[] = [
                    'from' => "XAU{$k}",
                    'to' => $row['to'],
                    'rate' => $row['rate'] / 24 * $k,
                ];

                $rates[] = [
                    'from' => $row['to'],
                    'to' => "XAU{$k}",
                    'rate' => 1 / $row['rate'] / 24 * $k,
                ];
            }
        }

        return $rates;
    }
}
