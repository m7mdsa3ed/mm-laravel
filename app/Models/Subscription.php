<?php

namespace App\Models;

use App\Actions\Subscriptions\SubscriptionRenewAction;
use App\Enums\IntervalUnitEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    public $fillable = [
        'user_id',
        'name',
        'amount',
        'interval_unit',
        'interval_count',
        'starts_at',
        'account_id',
        'category_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'interval_unit' => 'integer',
    ];

    protected $appends = [
        'expires_at',
    ];

    public $timestamps = false;

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

    public function getExpiresAtAttribute()
    {
        $date = Carbon::parse($this->starts_at);

        $unit = $this->interval_unit;

        $count = $this->interval_count;

        $expiresAt = match ($unit) {
            IntervalUnitEnum::Days() => $date->addDays($count),
            IntervalUnitEnum::Weeks() => $date->addWeeks($count),
            IntervalUnitEnum::Months() => $date->addMonths($count),
        };

        return $expiresAt;
    }

    public function renew($options = [])
    {
        dispatchAction(new SubscriptionRenewAction($this, $options));

        return $this;
    }
}
