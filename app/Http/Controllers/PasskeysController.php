<?php

namespace App\Http\Controllers;

use App\Services\Passkeys\DTOs\WebAuthApiDto;
use App\Services\Passkeys\PasskeysService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PasskeysController extends Controller
{
    public function __construct(
        private readonly PasskeysService $passkeysService,
    ) {

    }

    /** @throws Throwable */
    public function createArguments(Request $request): JsonResponse
    {
        $response = $this->passkeysService->createArgumentsForNewKey(WebAuthApiDto::fromRequest($request));

        return response()->json($response);
    }

    /** @throws Throwable */
    public function getArguments(Request $request): JsonResponse
    {
        $response = $this->passkeysService->getArgumentsForValidation(WebAuthApiDto::fromRequest($request));

        return response()->json($response);
    }

    /** @throws Throwable */
    public function createProcess(Request $request): JsonResponse
    {
        $response = $this->passkeysService->createProcessForNewKey(WebAuthApiDto::fromRequest($request));

        return response()->json($response);
    }

    /** @throws Throwable */
    public function getProcess(Request $request): JsonResponse
    {
        $response = $this->passkeysService->getProcessForValidation(WebAuthApiDto::fromRequest($request));

        return response()->json($response);
    }

    /** @throws Throwable */
    public function refreshCertificates(Request $request): JsonResponse
    {
        $response = $this->passkeysService->refreshCertificates(WebAuthApiDto::fromRequest($request));

        return response()->json($response);
    }

    public function getAllPassKeysView(): array
    {
        $userId = auth()->id();

        $passkeys = $this->passkeysService->getRegistrationsByUserId($userId);

        return [
            'viewContent' => view('passKeys', [
                'passKeys' => $passkeys,
            ])->render(),
        ];
    }

    public function viewAny()
    {
        $userId = auth()->id();

        $passkeys = $this->passkeysService->getRegistrationsByUserId($userId);

        $passkeys = $passkeys->map(function ($passkey) {
            return [
                'id' => $passkey->id,
                'created_at' => $passkey->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $passkey->updated_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()
            ->json($passkeys);
    }

    public function delete(int $id): void
    {
        $userId = auth()->id();

        $this->passkeysService->deleteRegistration($userId, $id);
    }
}
