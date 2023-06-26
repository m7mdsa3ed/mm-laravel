<?php

namespace App\Models;

use App\Enums\BudgetType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $name
 * @property string $description
 * @property float $amount
 * @property float $balance
 * @property int $type
 * @property int $user_id
 * @property int $category_id
 * @property-read float $progress
 * @property-read float $exceeded_amount
 * @property-read string $type_as_string
 * @property User $user
 * @property Category[] $categories
 */
class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'amount',
        'type',
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

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'budget_categories', 'budget_id', 'category_id');
    }

    public function balance(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => max($value, 0),
        );
    }

    public function progress(): Attribute
    {
        return Attribute::make(
            get: function () {
                $balance = $this->balance;

                if ($balance === null) {
                    return 0;
                }

                $progress = round($balance / $this->amount * 100, 2);

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
