<?php

namespace App\Enums;

use App\Traits\EnumInvokable;

enum SettingsTypeEnum: int
{
    use EnumInvokable;

    case String = 1;

    case Bool = 2;

    case Array = 3;
}
