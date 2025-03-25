<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiResponse;

class SaveApiResponse
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            // Only process successful JSON responses
            if ($response->isSuccessful() && $response->headers->get('content-type') === 'application/json') {
                ApiResponse::updateOrCreate(
                    ['url' => $request->path(), 'method' => $request->method()],
                    [
                        'response' => json_decode($response->getContent(), true),
                        'method' => $request->method(),
                        'status_code' => $response->status()
                    ]
                );
            }
        } catch (\Exception $e) {
            // Handle any exceptions that may occur during the database operation
            \Log::error('Failed to save API response: ' . $e->getMessage());
        }

        return $response;
    }
}
