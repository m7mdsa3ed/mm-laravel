<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function notifications(): JsonResponse
    {
        $user = auth()->user();

        $notificationQuery = $user->notifications();

        $unreadNotificationCount = (clone $notificationQuery)->whereNull('read_at')->count();

        $notifications = $notificationQuery
            ->limit(max($unreadNotificationCount, 10))
            ->latest('created_at')
            ->select('type', 'data', 'read_at', 'created_at')
            ->get();

        return response()
            ->json($notifications);
    }

    public function markAsRead(?int $notificationId = null)
    {
        $user = auth()->user();

        $user->notifications()
            ->newQuery()
            ->when($notificationId, fn ($query, $id) => $query->where('id', $id))
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        return response()
            ->noContent();
    }
}
