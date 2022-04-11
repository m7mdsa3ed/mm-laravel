<?php

namespace App\Models;

use App\Traits\HasFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFilter;

    public $fillable = [
        'type',
        'amount',
        'description',
        'details',
        'account_id',
        'user_id',
        'category_id',
        'is_public',
        'created_at',
    ];

    protected $casts = [
        'created_at'    => 'date:Y-m-d',
        'updated_at'    => 'date:Y-m-d',
    ];

    protected static function booted()
    {
        static::addGlobalScope('public', function (Builder $builder) {
            $builder->where('is_public', 1);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function scopeFilterByDates(Builder $query, array $dates)
    {
        [$date_from, $date_to] = $dates;

        $query
            ->when($date_from, function ($query) use ($date_from) {
                $query->whereDate('created_at', ">=", $date_from);
            })
            ->when($date_to, function ($query) use ($date_to) {
                $query->whereDate('created_at', "<=", $date_to);
            });
    }
}
