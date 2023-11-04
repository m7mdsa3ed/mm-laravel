<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountCard extends Model
{
    public $fillable = [
        'account_id',
        'name',
        'card_number',
        'brand',
        'type',
        'cvv',
        'expiration_month',
        'expiration_year',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public static function maskCardNumbers(\Illuminate\Database\Eloquent\Collection|array $cards)
    {
        return $cards->map(function (AccountCard $card) {
            $card->card_number = substr_replace($card->card_number, 'XXXX XXXX XXXX ', 0, 12);

            $card->cvv = 'XXX';

            return $card;
        });
    }
}
