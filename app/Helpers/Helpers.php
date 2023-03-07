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
