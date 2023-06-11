<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
    public $fillable = [
        'name',
        'user_id',
        'type_id',
        'currency_id',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function currencyRate(): HasOne
    {
        return $this->hasOne(CurrencyRate::class, 'from_currency_id', 'currency_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(AccountType::class, 'type_id');
    }

    public function scopeWithBalancies($query)
    {
        $query->select('accounts.*')
            ->leftJoin('transactions', 'transactions.account_id', 'accounts.id')
            ->groupBy('accounts.id');

        $cols = [
            'sum(if(transactions.action = 1, transactions.amount, -transactions.amount)) balance',
            'sum(if(transactions.action_type in (4), if(transactions.action = 1, -transactions.amount, transactions.amount), 0)) loans',
            'sum(if(transactions.action_type in (5), if(transactions.action = 1, transactions.amount, -transactions.amount), 0)) debits',
        ];

        foreach ($cols as $col) {
            $query->addSelect(DB::raw($col));
        }
    }
}
