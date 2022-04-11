<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TagTransaction extends Model
{
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }
}
