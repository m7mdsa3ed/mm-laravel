<?php

namespace App\Services\Settings;

use App\Enums\SettingsTypeEnum;
use App\Models\Settings;
use Exception;

class SettingsService
{
    public function save(string $key, mixed $value = null, ?SettingsTypeEnum $type = null): bool
    {
        try {
            cache()->forget(Settings::SETTINGS_CACHE_KEY);

            $uniqueBy = array_filter([
                'key' => $key,
                'type' => $type?->value,
            ]);

            Settings::query()
                ->updateOrCreate(
                    [...$uniqueBy],
                    ['value' => $value]
                );

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
