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

    public function scopeSelectBalance($query, $user, $select = '*')
    {
        $balanceQuery = DB::raw("ifnull((select sum( ifnull(if(type = 1, amount, amount * -1), 0) ) from transactions where transactions.category_id = categories.id and user_id = $user->id), 0) as balance");

        $query->select($select)
            ->addSelect($balanceQuery);
    }
}
