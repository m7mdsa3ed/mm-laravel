<?php

namespace App\Services\Settings;

use App\Enums\SettingsTypeEnum;
use App\Models\Settings;
use Exception;

class SettingsService
{
    public function save(string $key, mixed $value = null, ?SettingsTypeEnum $type = null, ?int $userId = null): bool
    {
        try {
            cache()->forget(Settings::SETTINGS_CACHE_KEY);

            $uniqueBy = array_filter([
                'key' => $key,
                'type' => $type?->value,
                'user_id' => $userId,
            ]);

            Settings::query()
                ->updateOrCreate(
                    [...$uniqueBy],
                    ['value' => $value]
                );

            return true;
        } catch (Exception) {
            return false;
        }
    }

    public function forget(string $key, ?int $userId = null)
    {
        Settings::query()
            ->where('key', $key)
            ->where('user_id', $userId)
            ->delete();
    }

    public function updateArrayKey(string $key, mixed $value, ?int $userId = null)
    {
        $currentSettings = settings($key, $userId, true);

        if ($currentSettings->type != SettingsTypeEnum::Array->value) {
            return false;
        }

        $newValue = $currentSettings->value ?? [];

        if (($currentValueKey = array_search($value, $newValue))) {
            unset($newValue[$currentValueKey]);
        } else {
            $newValue[] = $value;
        }

        return $this->save($key, $newValue, SettingsTypeEnum::from($currentSettings->type), $userId);
    }
}
