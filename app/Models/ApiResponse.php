<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class ApiResponse extends Model
{
    protected $fillable = [
        'response',
    ];

    public function response(): Attribute
    {
        return new Attribute(
            get: fn ($value) => unserialize($value)
        );
    }
}
