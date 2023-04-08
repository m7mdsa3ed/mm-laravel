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
