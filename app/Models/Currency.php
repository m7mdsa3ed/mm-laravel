<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'name',
    ];

    public $timestamps = false;

    public function rates()
    {
        return $this->hasMany(CurrencyRate::class, 'from_currency_id');
    }
}
