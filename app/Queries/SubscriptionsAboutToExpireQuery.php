<?php

namespace App\Queries;

use App\Models\Subscription;

class SubscriptionsAboutToExpireQuery
{
    public static function get(int $userId, int $expirationInDays = 7): mixed
    {
        return Subscription::query()
            ->where('user_id', $userId)
            ->whereRaw("DATEDIFF(expires_at, NOW()) <= {$expirationInDays}")
            ->get();
    }
}
