<?php

namespace App\Http\Requests;

use Closure;

class HttpRequest
{
    public function __construct(
        public string $method,
        public string $url,
        public array $params = [],
        public ?Closure $formatter = null,
        public array $listeners = []
    ) {
    }

    public function handler(self $request)
    {
        dd($request);
    }
}
