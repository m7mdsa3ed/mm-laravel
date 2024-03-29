<?php

namespace App\Traits;

trait HasInstanceGetter
{
    public static function getInstance(...$args): self
    {
        return app(static::class, ...$args);
    }
}
