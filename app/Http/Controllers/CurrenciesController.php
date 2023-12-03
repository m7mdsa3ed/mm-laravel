<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrenciesController extends Controller
{
    public function viewAny(): JsonResponse
    {
        $currencies = Currency::query()
            ->get();

        return response()->json($currencies);
    }

    public function getUserCurrenciesWithRates(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $user->load('currencies');

        $userCurrencyIds = $user->currencies->pluck('id')->unique();

        $data = $user
            ->currencies()
            ->with([
                'rates' => function ($query) use ($userCurrencyIds, $user) {
                    $query->whereIn('to_currency_id', $userCurrencyIds)
                        ->with([
                            'fromCurrency',
                            'toCurrency',
                            'userCurrencyRates' => function ($query) use ($user) {
                                $query->where('user_id', $user->id);
                            },
                        ]);
                },
            ])
            ->get()
            ->unique('id');

        return response()->json($data);
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
