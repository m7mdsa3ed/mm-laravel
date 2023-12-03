<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurrencyRate extends Model
{
    protected $fillable = [
        'from_currency_id',
        'to_currency_id',
        'rate',
    ];

    protected static function booted(): void
    {
        static::saved(function ($currencyRate) {
            $currencyRate->updateOppositeRate();
        });
    }

    public function fromCurrency()
    {
        return $this->belongsTo(Currency::class, 'from_currency_id');
    }

    public function toCurrency()
    {
        return $this->belongsTo(Currency::class, 'to_currency_id');
    }

    public function userCurrencyRates(): HasMany
    {
        return $this->hasMany(UserCurrencyRate::class);
    }

    public function updateOppositeRate(): void
    {
        self::withoutEvents(
            fn () => self::query()
                ->updateOrCreate([
                    'from_currency_id' => $this->to_currency_id,
                    'to_currency_id' => $this->from_currency_id,
                ], ['rate' => 1 / $this->rate])
        );
    }

    public function ratesFormatting(array $rates): array
    {
        $methodFormatter = fn ($type, $name) => str(implode('_', [$type, $name, 'rate', 'transformer']))
            ->lower()
            ->camel()
            ->toString();

        return array_map(function ($row) use ($methodFormatter) {
            ['to' => $to, 'from' => $from, 'rate' => $rate] = $row;

            $toTransformerMethodName = $methodFormatter('to', $to);

            $fromTransformerMethodName = $methodFormatter('from', $from);

            $method = method_exists($this, $toTransformerMethodName) ? $toTransformerMethodName : null;

            $method ??= method_exists($this, $fromTransformerMethodName) ? $fromTransformerMethodName : null;

            if ($method) {
                $rate = $this->{$method}($rate);
            }

            return [
                'from' => $from,
                'to' => $to,
                'rate' => $rate,
            ];
        }, $rates);
    }

    public function toXauRateTransformer(float $rate): float
    {
        return $rate * 31.1034807;
    }

    public function fromXauRateTransformer(float $rate): float
    {
        return $rate / 31.1034807;
    }
}
