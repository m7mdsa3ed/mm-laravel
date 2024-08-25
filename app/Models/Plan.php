<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    public $fillable = [
        'name',
        'description',
    ];

    protected $appends = [
        'total_price',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PlanItem::class);
    }

    public function totalPrice(): Attribute
    {
        return Attribute::make(
            get: function () {
                $itemsRelationLoaded = $this->relationLoaded('items');

                if ($itemsRelationLoaded) {
                    return $this->items->sum('price');
                }

                return 0;
            }
        );
    }
}
