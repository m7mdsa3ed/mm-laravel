<?php

namespace App\Models;

use App\Actions\Subscriptions\SubscriptionRenewAction;
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
    ];

    protected $casts = [
        'started_at' => 'datetime',
    ];

    protected $appends = [
        'expires_at',
    ];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getExpiresAtAttribute()
    {
        $date = Carbon::parse($this->starts_at);

        $unit = $this->interval_unit;

        $count = $this->interval_count;

        $expiresAt = match ($unit) {
            1 => $date->addDays($count),
            2 => $date->addWeeks($count),
            3 => $date->addMonths($count),
            4 => $date->addDays($count),
        };

        return $expiresAt;
    }

    public function renew($options = [])
    {
        dispatchAction(new SubscriptionRenewAction($this, $options));

        return $this;
    }
}
