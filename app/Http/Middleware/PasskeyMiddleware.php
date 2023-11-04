<?php

namespace App\Http\Middleware;

use App\Services\Passkeys\DTOs\WebAuthApiDto;
use App\Services\Passkeys\PasskeysService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Throwable;
use Exception;

class PasskeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $credentials = json_decode($request->header('PasskeyCredentials'), true);

            if (!$credentials) {
                throw new Exception('Passkey credentials not found');
            }

            /** @var PasskeysService $passkeysService */
            $passkeysService = app(PasskeysService::class);

            $request->merge($credentials);

            $dto = WebAuthApiDto::fromRequest($request);

            $passkeysService->getProcessForValidation($dto);

            return $next($request);
        } catch (Throwable) {
            abort(401, 'Passkey authentication failed');
        }
    }
}
