<?php

function dispatchAction(App\Actions\Action $action)
{
    return $action->execute();
}

if ( ! function_exists('r')) {
    function r(string $string): string
    {
        return str($string)
            ->camel()
            ->ucfirst()
            ->ucsplit()
            ->join(' ');
    }
}

if ( ! function_exists('settings')) {
    function settings(mixed $key = null): mixed
    {
        if (isset($key)) {
            return App\Models\Settings::getByKey($key);
        }

        return app(App\Services\Settings\SettingsService::class);
    }
}

if ( ! function_exists('liveResponse')) {
    function liveResponse(callable $callback): void
    {
        set_time_limit(0);

        ob_implicit_flush(1);

        ob_end_flush();

        header('X-Accel-Buffering: no');

        $callback();
    }
}
