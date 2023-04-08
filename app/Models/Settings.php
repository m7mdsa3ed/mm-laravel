<?php

namespace App\Models;

use App\Enums\SettingsTypeEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $fillable = [
        'key',
        'type',
        'value',
        'user_id',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public $appends = [
        'type_as_string',
    ];

    public const SETTINGS_CACHE_KEY = 'settings_cache';

    public static function getByKey(mixed $key, ?int $userId = null, bool $fullObject = false): mixed
    {
        $settings = self::getAll($userId);

        if (is_array($key)) {
            if (count($key)) {
                return $settings->whereIn('key', $key)->toArray();
            }

            return $settings->toArray();
        }

        if ($fullObject) {
            return $settings->where('key', $key)->first();
        }

        return $settings->where('key', $key)->first()?->value;
    }

    public static function getAll(?int $userId = null)
    {
        return cache()
            ->rememberForever(self::SETTINGS_CACHE_KEY, function () use ($userId) {
                return static::query()
                    ->when($userId, fn ($query, $userId) => $query->whereUserId($userId))
                    ->get();
            });
    }

    public function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? str($this->key)->studly()->ucsplit()->join(' '),
            set: fn ($value) => $value ?? str($this->key)->studly()->ucsplit()->join(' '),
        );
    }

    public function typeAsString(): Attribute
    {
        return new Attribute(
            get: fn () => SettingsTypeEnum::from($this->type)->name
        );
    }
}
