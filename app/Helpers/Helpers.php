<?php

function dispatchAction(App\Actions\Action $action)
{
    return $action->execute();
}

if (!function_exists('r')) {
    function r(string $string): string
    {
        return str($string)
            ->camel()
            ->ucfirst()
            ->ucsplit()
            ->join(' ');
    }
}

if (!function_exists('settings')) {
    function settings(mixed $key = null, ?int $userId = null, bool $fullObject = false): mixed
    {
        if (isset($key)) {
            return App\Models\Settings::getByKey($key, $userId, $fullObject);
        }

        return app(App\Services\Settings\SettingsService::class);
    }
}

if (!function_exists('liveResponse')) {
    function liveResponse(callable $callback): void
    {
        set_time_limit(0);

        ob_implicit_flush(1);

        if (ob_get_contents()) {
            ob_end_clean();
        }

        header('X-Accel-Buffering: no');

        $callback();
    }
}

if (!function_exists('money')) {
    function money(mixed $value, string $currency = 'USD'): string
    {
        return number_format($value, 2) . ' ' . $currency;
    }
}

if (!function_exists('parsePeriodToDates')) {
    function parsePeriodToDates(string $periodAsString, mixed $from = null, mixed $to = null): array
    {
        $supportedPeriods = \App\Services\App\AppService::getInstance()
            ->getStatsPeriods();

        if (!in_array($periodAsString, $supportedPeriods)) {
            $periodAsString = 'thisMonth';
        }

        return match ($periodAsString) {
            'thisMonth' => [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ],
            'lastMonth' => [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
            ],
            'thisYear' => [
                now()->startOfYear(),
                now()->endOfYear(),
            ],
            'lastYear' => [
                now()->subYear()->startOfYear(),
                now()->subYear()->endOfYear(),
            ],
            'range' => [
                \Illuminate\Support\Carbon::parse($from),
                \Illuminate\Support\Carbon::parse($to),
            ],
        };
    }
}
