<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PassKey extends Model
{
    use HasFactory;

    protected $table = 'passkeys';

    protected $fillable = [
        'user_id',
        'payload',
    ];

    public function payload(): Attribute
    {
        return Attribute::make(
            get: fn ($payload) => (object) recursiveBase64Decode(json_decode($payload, true)),
            set: fn ($payload) => json_encode(recursiveBase64Encode((array) $payload)),
        );
    }
}
