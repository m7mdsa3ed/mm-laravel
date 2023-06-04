<?php

namespace App\Http\Controllers;

use App\Services\Settings\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    public function viewAny(): JsonResponse
    {
        return response()->json([
            'settings' => settings([]),
        ]);
    }

    /** @throws ValidationException */
    public function save(Request $request, SettingsService $settingsService): JsonResponse
    {
        $this->validate($request, [
            'key' => 'required',
        ]);

        $successful = $settingsService->save(
            key: $request->get('key'),
            value: $request->get('value'),
            userId: auth()->id(),
        );

        return response()->json([
            'status' => $successful ? 'success' : 'failed',
        ]);
    }

    public function updatePinAccounts(SettingsService $settingsService, int $accountId): Response
    {
        $userId = auth()->id();

        $pinnedAccounts = settings('pinnedAccounts', $userId) ?? [];

        $pinnedAccounts = in_array($accountId, $pinnedAccounts)
            ? array_diff($pinnedAccounts, [$accountId])
            : [...$pinnedAccounts, $accountId];

        $settingsService->save(
            key: 'pinnedAccounts',
            value: $pinnedAccounts,
            userId: $userId,
        );

        return response()->noContent();
    }
}
