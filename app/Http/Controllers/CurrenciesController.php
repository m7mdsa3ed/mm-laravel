<?php

namespace App\Http\Controllers;

use App\Models\Currency;

class CurrenciesController extends Controller
{
    public function viewAny()
    {
        return Currency::with([
            'rates.fromCurrency',
            'rates.toCurrency',
        ])->get();
    }
}
