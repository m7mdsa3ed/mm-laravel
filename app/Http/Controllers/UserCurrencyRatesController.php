<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserCurrencyRatesController extends Controller
{
    public function save(Request $request, int $currencyRateId)
    {
        $this->validate($request, [
            'rate' => 'required|numeric',
        ]);

        $user = auth()->user();

        $user->currencyRates()->updateOrCreate([
            'currency_rate_id' => $currencyRateId,
        ], [
            'rate' => $request->get('rate'),
        ]);

        return response()->json([
            'message' => 'Saved',
        ]);
    }

    public function reset(Request $request, int $userCurrencyRateId): JsonResponse
    {
        $user = $request->user();

        $user->currencyRates()
            ->where('id', $userCurrencyRateId)
            ->update([
                'rate' => null
            ]);

        return response()->json([
            'message' => 'Reset Successfully',
        ]);
    }
}
