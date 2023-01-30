<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Category extends Model
{
    public $fillable = [
        'name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeWithBalancies($query)
    {
        $query->select('categories.*')
            ->join('transactions', 'transactions.category_id', 'categories.id')
            ->groupBy('categories.id');

        $cols = [
            'sum(if(transactions.action = 1, transactions.amount, -transactions.amount)) balance',
        ];

        foreach ($cols as $col) {
            $query->addSelect(DB::raw($col));
        }
    }
}
