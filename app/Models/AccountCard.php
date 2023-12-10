<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountCard extends Model
{
    public $fillable = [
        'account_id',
        'name',
        'brand',
        'type',
        'last_4',
        'encrypted_payload',
        'expiration_month',
        'expiration_year',
    ];

    protected $casts = [
        'encrypted_payload' => 'encrypted'
    ];

    protected $hidden = [
        'encrypted_payload'
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public static function maskCardNumbers(\Illuminate\Database\Eloquent\Collection|array $cards)
    {
        return $cards->map(function (AccountCard $card) {
            $card->card_number = "XXXX XXXX XXXX {$card->last_4}";

            $card->cvv = 'XXX';

            return $card;
        });
    }
}
