<?php

namespace App\Http\Middleware;

use App\Models\RequestLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use UAParser\Parser;
use Exception;

class LogRequestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $log = new RequestLog();

        $log->forceFill([
            'method' => $request->method(),
            'url' => $request->url(),
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
            'payload' => [
                'body' => encrypt($request->all()),
                'uaParsed' => $this->parseUa($request),
            ],
        ]);

        $log->save();

        info('Request Log', $log->toArray());

        return $next($request);
    }

    private function parseUa(Request $request)
    {
        $userAgent = $request->userAgent();

        if ($userAgent === null) {
            return null;
        }

        try {
            $parser = Parser::create();

            $client = $parser->parse($userAgent);

            return [
                'ua' => [
                    'family' => $client->ua->family,
                    'major' => $client->ua->major,
                    'minor' => $client->ua->minor,
                    'patch' => $client->ua->patch,
                    'toString' => $client->ua->toString(),
                    'toVersion' => $client->ua->toVersion(),
                ],
                'os' => [
                    'family' => $client->os->family,
                    'major' => $client->os->major,
                    'minor' => $client->os->minor,
                    'patch' => $client->os->patch,
                    'patchMinor' => $client->os->patchMinor,
                    'toString' => $client->os->toString(),
                    'toVersion' => $client->os->toVersion(),
                ],
                'device' => [
                    'family' => $client->device->family,
                ],
                'toString' => $client->toString(),
                'sec-ch-ua' => [
                    'sec-ch-ua' => $request->header('sec-ch-ua'),
                    'sec-ch-ua-mobile' => $request->header('sec-ch-ua-mobile'),
                    'sec-ch-ua-platform' => $request->header('sec-ch-ua-platform'),
                    'sec-ch-ua-full-version' => $request->header('sec-ch-ua-full-version'),
                    'sec-ch-ua-arch' => $request->header('sec-ch-ua-arch'),
                    'sec-ch-ua-model' => $request->header('sec-ch-ua-model'),
                    'sec-ch-ua-platform-version' => $request->header('sec-ch-ua-platform-version'),
                    'sec-ch-ua-platform-model' => $request->header('sec-ch-ua-platform-model'),
                    'sec-ch-ua-bitness' => $request->header('sec-ch-ua-bitness'),
                ],
            ];
        } catch (Exception) {

        }
    }
}
