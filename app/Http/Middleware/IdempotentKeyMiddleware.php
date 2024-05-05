<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdempotentKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->method() !== 'POST') {
            return $next($request);
        }

        $key = $request->header('X-Idempotent-Key');

        if (!$key) {
            abort(422, 'X-Idempotency-Key header is required');
        }

        $alreadyExists = cache()->has('idempotent:' . $key);

        if ($alreadyExists) {
            abort(409, 'Request already processed');
        }

        cache()->forever('idempotent:' . $key, true);

        return $next($request);
    }
}
