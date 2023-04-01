<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\CurrencyRate;
use Illuminate\Http\Request;

class CurrenciesController extends Controller
{
    public function viewAny()
    {
        return Currency::with([
            'rates.fromCurrency',
            'rates.toCurrency',
        ])->get();
    }

    public function save(Request $request, Currency $currency)
    {
        $this->validate($request, [
            'name' => 'string',
        ]);

        $currency->fill([
            ...$request->only([
                'name',
            ]),
        ]);

        $currency->save();

        return $currency;
    }

    public function updateRate(Request $request, CurrencyRate $currencyRate)
    {
        $this->validate($request, [
            'rate' => 'required',
        ]);

        $currencyRate->update([
            'rate' => $request->rate,
        ]);

        return response()
            ->json(
                $currencyRate->loadMissing([
                    'fromCurrency',
                    'toCurrency',
                ])
            );
    }
}
