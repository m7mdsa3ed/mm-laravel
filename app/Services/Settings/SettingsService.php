<?php

namespace App\Services\Settings;

use App\Models\Settings;
use App\Services\Settings\DTOs\SettingsData;
use Exception;

class SettingsService
{
    public function save(SettingsData $data): bool
    {
        try {
            cache()->forget(Settings::SETTINGS_CACHE_KEY);

            $uniqueBy = array_filter([
                'key' => $data->key,
                'type' => $data->type?->value,
                'user_id' => $data->userId,
            ]);

            Settings::query()
                ->updateOrCreate(
                    [...$uniqueBy],
                    ['value' => $data->value]
                );

            return true;
        } catch (Exception) {
            return false;
        }
    }

    public function forget(string $key, ?int $userId = null)
    {
        cache()->forget(Settings::SETTINGS_CACHE_KEY);

        Settings::query()
            ->where('key', $key)
            ->where('user_id', $userId)
            ->delete();
    }
}
