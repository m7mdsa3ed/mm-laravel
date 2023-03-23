<?php

namespace App\Traits;

trait HasInitializer
{
    public static function getInstance(): self
    {
        return app(static::class);
    }
}
