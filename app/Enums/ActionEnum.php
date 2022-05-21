<?php

namespace App\Enums;

use App\Traits\EnumInvokable;

enum ActionEnum: int
{
    use EnumInvokable;

    case IN = 1;
    case OUT = 2;
}
