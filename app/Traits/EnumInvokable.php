<?php

namespace App\Traits;

trait EnumInvokable
{
    public static function __callStatic($caseName, $args)
    {
        $cases = static::cases();

        $case = array_search($caseName, array_column($cases, 'name'));

        if ($case != false) {
            $case = $cases[$case];

            return $case->value ?? $case->name;
        }

        throw new \Exception('Undefined Case ' . $caseName);
    }
}
