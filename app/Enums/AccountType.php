<?php

namespace App\Enums;

use App\Traits\EnumInvokable;

enum AccountType: int
{
    use EnumInvokable;

    case Pocket = 1;
    case MoneySafe = 2;
    case Bank = 3;
    case DigitalWallet = 4;

    public static function getName(int $typeId)
    {
        $name = collect(self::cases())->where('value', $typeId)->first()?->name;

        if ($name) {
            $name = str($name)->ucsplit()->join(' ');
        }

        return $name;
    }

    public function onHand($type): bool
    {
        return match ($type) {
            self::Pocket => true,
            default => false
        };
    }
}
