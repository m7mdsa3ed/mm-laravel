<?php

namespace App\Models;

use App\Enums\ActionTypeEnum;
use App\Traits\HasFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Transaction extends Model
{
    use HasFilter;

    public $fillable = [
        'type',
        'action',
        'action_type',
        'amount',
        'description',
        'batch_id',
        'account_id',
        'user_id',
        'category_id',
        'is_public',
        'is_countable',
        'created_at',
    ];

    protected $appends = [

    ];

    protected $casts = [
        'created_at' => 'date:Y-m-d',
        'updated_at' => 'date:Y-m-d',
        'action_type' => 'integer',
        'amount' => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function ($transaction) {
            $transaction->action ??= ActionTypeEnum::getAction($transaction->action_type);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function contact(): HasOneThrough
    {
        return $this->hasOneThrough(Contact::class, TransactionContact::class, 'transaction_id', 'id', 'id', 'contact_id');
    }

    public function getActionTypeAsStringAttribute()
    {
        return ActionTypeEnum::getName($this->action_type);
    }

    public function scopeFilterByDates(Builder $query, array $dates)
    {
        [$date_from, $date_to] = $dates;

        $query
            ->when($date_from, function ($query) use ($date_from) {
                $query->whereDate('created_at', '>=', $date_from);
            })
            ->when($date_to, function ($query) use ($date_to) {
                $query->whereDate('created_at', '<=', $date_to);
            });
    }

    public function scopeFilterByPeriod(Builder $query, string $period)
    {
        switch ($period) {
            case '1':
                $query->whereDate('created_at', today());

                break;
            case '2':
                $query->whereDate('created_at', '>=', now()->startOfWeek())->whereDate('created_at', '<=', now()->endOfWeek());

                break;
            case '3':
                $query->whereDate('created_at', '>=', now()->startOfMonth())->whereDate('created_at', '<=', now()->endOfMonth());

                break;
        }
    }

    public function scopeFilterByTags(Builder $query, $tags)
    {
        $tags = is_array($tags) ? $tags : [$tags];

        $query->whereHas('tags', function ($query) use ($tags) {
            $query->whereIn('tags.id', $tags);
        });
    }

    public function scopeFilterByDescription(Builder $query, $term): void
    {
        $query->where('description', 'like', "%$term%");
    }
}
