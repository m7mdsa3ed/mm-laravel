<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $name
 * @property string $description
 * @property float $amount
 */
class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'amount',
        'user_id',
        'category_id',
    ];

    /** @return BelongsTo */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
