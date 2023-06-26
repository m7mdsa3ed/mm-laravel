<?php

namespace App\Models;

use App\Enums\BudgetType;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    protected $appends = [
        'progress',
        'type_as_string',
        'exceeded_amount',
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

    public function progress(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->getRawOriginal('balance') === null) {
                    return 0;
                }

                $progress = round($this->getRawOriginal('balance') / $this->amount * 100, 2);

                return min($progress, 100);
            }
        );
    }

    public function exceededAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->getRawOriginal('balance') === null) {
                    return 0;
                }

                $exceededAmount = $this->getRawOriginal('balance') - $this->amount;

                return max($exceededAmount, 0);
            }
        );
    }

    public function typeAsString(): Attribute
    {
        return Attribute::make(
            get: function () {
                $type = $this->type;

                try {
                    return BudgetType::from($type)->name;
                } catch (\Throwable) {
                    return null;
                }
            }
        );
    }
}
