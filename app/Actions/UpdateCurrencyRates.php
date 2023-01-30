<?php

namespace App\Actions;

use App\ThirdParty\XeScrapping;
use Illuminate\Support\Facades\Http;

class UpdateCurrencyRates extends Action
{
    public function __construct(array $args = [])
    {
        $this->args = $args;
    }

    public function execute()
    {
        $validator = $this->validate($this->args, [
            'From' => 'required|string',
            'To' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        $xeScrapping = new XeScrapping();

        $request = $xeScrapping->getRequest($this->args);

        $response = Http::execute($request);

        return $response;
    }
}
