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
            ->pluck('slug', 'id');
    }

    public static function getTransformations(array $currencySlugs): array
    {
        $transformations = [];

        foreach ($currencySlugs as $key => $from) {
            $transformations = [
                ...$transformations,
                ...array_map(fn ($to) => [
                    'From' => $from,
                    'To' => $to,
                ], array_filter($currencySlugs, fn ($to) => $to !== $from)),
            ];

            unset($currencySlugs[$key]);
        }

        return $transformations;
    }
}
