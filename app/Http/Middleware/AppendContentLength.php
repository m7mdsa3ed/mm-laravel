<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppendContentLength
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $contentAsString = is_string(($content = $response->getOriginalContent()))
            ? $content
            : @(json_encode($content));

        $contentLength = str($contentAsString)->length();

        $response->header('Content-Length', $contentLength);

        return $response;
    }
}
