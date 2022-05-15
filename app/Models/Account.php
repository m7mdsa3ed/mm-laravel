<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
    public $fillable = [
        'name',
        'user_id',
        'currency_id',
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

    public function scopeDefaultSelect($query, $select = 'accounts.*')
    {
        static $selectedAlready = false;

        if (!$selectedAlready) {
            $selectedAlready = true;

            $query->select($select);
        }
    }

    public function scopeSelectBalance($query, $user)
    {
        $balanceQuery = DB::raw("ifnull((select sum( ifnull(if(type = 1, amount, amount * -1), 0) ) from transactions where transactions.account_id = accounts.id and user_id = $user->id), 0) as balance");

        $query->defaultSelect()
            ->addSelect($balanceQuery);
    }

    public function scopeSelectBalanceForCurrency($query, $user, $currencyId)
    {
        $amountCase = "case when accounts.currency_id != $currencyId and from_currency_id = accounts.currency_id then amount * rate else amount end";

        $balanceQuery = DB::raw("ifnull((select sum( ifnull(if(type = 1, ($amountCase), ($amountCase) * -1), 0) ) from transactions where transactions.account_id = accounts.id and user_id = $user->id), 0) as balanceInDefaultCurrency");

        $query->defaultSelect()
            ->addSelect($balanceQuery)
            ->join('currency_rates', 'currency_rates.from_currency_id', '=', 'accounts.currency_id');

        return $query;
    }
}
