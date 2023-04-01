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
}
