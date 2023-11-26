<?php

namespace App\Enums;

use App\Traits\EnumInvokable;

enum IntervalUnitEnum: int
{
    use EnumInvokable;

    case SECOND = 1;

    case MINUTE = 2;

    case HOUR = 3;

    case DAY = 4;

    case WEEK = 5;

    case MONTH = 6;

    case YEAR = 7;
}
