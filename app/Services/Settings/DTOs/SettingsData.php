<?php

namespace App\Services\Settings\DTOs;

use App\Enums\SettingsTypeEnum;
use Illuminate\Http\Request;

class SettingsData
{
    public function __construct(
        public string $key,
        public mixed $value,
        public int $userId,
        public ?SettingsTypeEnum $type = null,
        public bool $isPublic = true
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            key: $request->get('key'),
            value: $request->get('value'),
            userId: $request->get('user_id'),
            type: $request->get('type'),
            isPublic: $request->get('is_public', true),
        );
    }
}
