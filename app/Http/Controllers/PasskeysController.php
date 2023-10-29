<?php

namespace App\Http\Controllers;

use App\Http\Requests\WebAuthApiRequest;
use App\Services\Passkeys\PasskeysService;
use Illuminate\Http\JsonResponse;
use Throwable;

class PasskeysController extends Controller
{
    public function __construct(
        private readonly PasskeysService $passkeysService,
    ) {

    }

    /** @throws Throwable */
    public function createArguments(WebAuthApiRequest $request): JsonResponse
    {
        $response = $this->passkeysService->createArgumentsForNewKey($request);

        return response()->json($response);
    }

    /** @throws Throwable */
    public function getArguments(WebAuthApiRequest $request): JsonResponse
    {
        $response = $this->passkeysService->getArgumentsForValidation($request);

        return response()->json($response);
    }

    /** @throws Throwable */
    public function createProcess(WebAuthApiRequest $request): JsonResponse
    {
        $response = $this->passkeysService->createProcessForNewKey($request);

        return response()->json($response);
    }

    /** @throws Throwable */
    public function getProcess(WebAuthApiRequest $request): JsonResponse
    {
        $response = $this->passkeysService->getProcessForValidation($request);

        return response()->json($response);
    }

    /** @throws Throwable */
    public function refreshCertificates(WebAuthApiRequest $request): JsonResponse
    {
        $response = $this->passkeysService->refreshCertificates($request);

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

    public function deleteRegistration(int $id): void
    {
        $userId = auth()->id();

        $this->passkeysService->deleteRegistration($userId, $id);
    }
}
