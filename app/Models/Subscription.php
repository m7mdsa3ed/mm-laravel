<?php

namespace App\Models;

use App\Enums\IntervalUnitEnum;
use App\Traits\HasAppendSelect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use DB;

class Subscription extends Model
{
    use HasFactory;
    use HasAppendSelect;

    protected $fillable = [
        'user_id',
        'account_id',
        'name',
        'amount',
        'interval_unit',
        'interval_count',
        'auto_renewal',
        'can_cancel',
        'is_active',
        'expires_at',
        'started_at',
    ];

    protected $casts = [
        'interval_unit' => IntervalUnitEnum::class,
        'auto_renewal' => 'boolean',
        'can_cancel' => 'boolean',
        'expires_at' => 'datetime:Y-m-d H:i:s',
        'started_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('addRemainingDaysScope', function (Builder $builder) {
            $tableName = $builder->getModel()->getTable();

            $builder->appendSelect([
                DB::raw("DATEDIFF({$tableName}.expires_at, NOW()) AS remaining_days"),
            ]);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canRenewBeforeExpiration(): bool
    {
        $inActivePeriod = now()->between($this->started_at, $this->expires_at);

        return !($inActivePeriod && $this->auto_renewal && !$this->can_cancel);
    }

    public static function nextExpirationDate(IntervalUnitEnum $unit, int $count, Carbon $startedAt): Carbon
    {
        $addInternalFunction = match ($unit) {
            IntervalUnitEnum::SECOND => fn($date) => $date->addSeconds($count),
            IntervalUnitEnum::MINUTE => fn($date) => $date->addMinutes($count),
            IntervalUnitEnum::HOUR => fn($date) => $date->addHours($count),
            IntervalUnitEnum::DAY => fn($date) => $date->addDays($count),
            IntervalUnitEnum::WEEK => fn($date) => $date->addWeeks($count),
            IntervalUnitEnum::MONTH => fn($date) => $date->addMonths($count),
            IntervalUnitEnum::YEAR => fn($date) => $date->addYears($count),
        };

        return $addInternalFunction($startedAt);
    }
}
