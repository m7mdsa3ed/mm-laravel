<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];

    protected $appends = [
        'changes',
    ];

    public function changes(): Attribute
    {
        return Attribute::make(
            get: fn () => array_diff_assoc($this->after ?? [], $this->before ?? [])
        );
    }
}
