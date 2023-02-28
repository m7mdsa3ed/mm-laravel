<?php

namespace App\Models;

use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
    public $fillable = [
        'name',
        'user_id',
        'type_id',
        'currency_id',
    ];

    public $appends = [
        'type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function currencyRate()
    {
        return $this->hasOne(CurrencyRate::class, 'from_currency_id', 'currency_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
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

    public function type(): Attribute
    {
        return Attribute::make(
            get: fn () => AccountType::getName($this->type_id)
        );
    }
}
