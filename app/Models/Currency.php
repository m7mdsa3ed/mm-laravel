<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Currency extends Model
{
    protected $fillable = [
        'slug',
        'name',
    ];

    public $timestamps = false;

    public function rates(): HasMany
    {
        return $this->hasMany(CurrencyRate::class, 'from_currency_id');
    }

    public function convertMoney(float $amount, self $toCurrency, ?float &$rate = null): float
    {
        $rate ??= ($toCurrencyRate = $this->rates->where('to_currency_id', $toCurrency->id)->first())
            ? $toCurrencyRate->rate
            : 0;

        return $rate * $amount;
    }

    public static function getSlugs(): Collection
    {
        return self::query()
            ->pluck('slug');
    }

    public static function getTransformationsFromCurrencies(array $currencies): array
    {
        $transformations = [];

        foreach ($currencies as $from) {
            $transformations = [
                ...$transformations,
                ...array_map(fn ($to) => [
                    'From' => $from,
                    'To' => $to,
                ], array_filter($currencies, fn ($to) => $to !== $from)),
            ];
        }

        return $transformations;
    }

    public function XeScrappingListener(array $response)
    {
        [
            'from' => $from,
            'to' => $to,
            'rate' => $rate,
        ] = $response;

        $methodFormatter = fn ($type, $name) => str(implode('_', [$type, $name, 'rate', 'transformer']))
            ->lower()
            ->camel()
            ->toString();

        $toTransformerMethodName = $methodFormatter('to', $to);

        $fromTransformerMethodName = $methodFormatter('from', $from);

        $method = method_exists($this, $toTransformerMethodName) ? $toTransformerMethodName : null;

        $method ??= method_exists($this, $fromTransformerMethodName) ? $fromTransformerMethodName : null;

        $rate = $method
            ? $this->{$method}($rate)
            : $rate;

        $fromCurrency = Currency::updateOrCreate(['slug' => $from]);

        $toCurrency = Currency::updateOrCreate(['slug' => $to]);

        CurrencyRate::updateOrCreate([
            'from_currency_id' => $fromCurrency->id,
            'to_currency_id' => $toCurrency->id,
        ], ['rate' => $rate]);
    }

    private function toXauRateTransformer(float $rate): float
    {
        return $rate * 31.1034807;
    }

    private function fromXauRateTransformer(float $rate): float
    {
        return $rate / 31.1034807;
    }
}
