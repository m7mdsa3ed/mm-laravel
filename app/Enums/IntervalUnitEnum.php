<?php

namespace App\Enums;

use App\Traits\EnumInvokable;

enum IntervalUnitEnum: int
{
    use EnumInvokable;

    case Days = 1;
    case Weeks = 2;
    case Months = 3;
}
