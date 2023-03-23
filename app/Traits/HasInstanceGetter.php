<?php

namespace App\Traits;

trait HasInstanceGetter
{
    public static function getInstance(): self
    {
        return app(static::class);
    }
}
