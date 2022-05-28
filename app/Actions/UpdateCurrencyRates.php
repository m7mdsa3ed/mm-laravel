<?php

namespace App\Actions;

use App\ThirdParty\XeScrapping;
use Illuminate\Support\Facades\Http;

class UpdateCurrencyRates extends Action
{
    public function execute($args)
    {
        $validator = $this->validate($args, [
            'From' => 'required|string',
            'To' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        $xeScrapping = new XeScrapping;

        $request = $xeScrapping->getRequest($args);

        $response = Http::execute($request);

        return $response;
    }
}
