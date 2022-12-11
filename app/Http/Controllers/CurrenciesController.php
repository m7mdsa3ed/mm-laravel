<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;

class CurrenciesController extends Controller
{
    public function viewAny()
    {
        return Currency::with([
            'rates',
            'rates.fromCurrency',
            'rates.toCurrency',
        ])->get();
    }
}
