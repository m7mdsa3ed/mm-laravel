<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class NotificationControler extends Controller
{
    public function notifications(): JsonResponse
    {
        return response()->json(auth()->user()->notifications->map(fn ($notification) => [
            'type' => last(explode('\\', $notification->type)),
            'data' => $notification->data,
        ]));
    }
}
