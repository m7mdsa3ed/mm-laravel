<?php

namespace App\Http\Controllers;

use App\Models\Currency;
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
}
