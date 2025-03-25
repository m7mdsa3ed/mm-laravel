<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiResponse extends Model
{
    protected $fillable = ['url', 'response', 'method', 'status_code'];

    protected $casts = [
        'response' => 'array'
    ];
}
