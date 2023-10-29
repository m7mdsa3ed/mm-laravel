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

        ob_end_clean();

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

if (!function_exists('recursiveBase64Encode')) {
    function recursiveBase64Encode($value): string|array
    {
        if (is_array($value)) {
            return array_map('recursiveBase64Encode', $value);
        }

        return base64_encode($value);
    }
}
if (!function_exists('recursiveBase64Decode')) {
    function recursiveBase64Decode($value): string|array
    {
        if (is_array($value)) {
            return array_map('recursiveBase64Decode', $value);
        }

        return base64_decode($value);
    }
}
