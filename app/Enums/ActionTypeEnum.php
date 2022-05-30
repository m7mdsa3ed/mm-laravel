<?php

namespace App\Enums;

use App\Traits\EnumInvokable;

enum ActionTypeEnum: int
{
    use EnumInvokable;

    case INCOME = 1;
    case OUTCOME = 2;
    case MOVE = 3;
    case LOAN = 4;
    case DEBIT = 5;

    public static function getName($value)
    {
        $cases = static::cases();

        $case = array_search($value, array_column($cases, 'value'));

        return str($cases[$case]->name)->lower()->ucfirst();
    }

    public static function getAction($case)
    {
        $creditCases = [
            ActionTypeEnum::INCOME(),
            ActionTypeEnum::LOAN(),
        ];

        return in_array($case, $creditCases) ? ActionEnum::IN() : ActionEnum::OUT();
    }
}
