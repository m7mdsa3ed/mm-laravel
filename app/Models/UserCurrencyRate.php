<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCurrencyRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'currency_rate_id',
        'rate',
    ];

    protected $casts = [
        'rate' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currencyRate(): BelongsTo
    {
        return $this->belongsTo(CurrencyRate::class);
    }
}
