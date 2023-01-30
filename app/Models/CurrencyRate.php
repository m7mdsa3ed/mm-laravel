<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyRate extends Model
{
    protected $fillable = [
        'from_currency_id',
        'to_currency_id',
        'rate',
    ];

    protected static function booted()
    {
        static::saved(function ($currencyRate) {
            self::withoutEvents(
                fn () => self::updateOrCreate([
                    'from_currency_id' => $currencyRate->to_currency_id,
                    'to_currency_id' => $currencyRate->from_currency_id,
                ], ['rate' => 1 / $currencyRate->rate])
            );
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
}
