<?php

namespace App\Http\Controllers;

use App\Models\AccountType;
use App\Services\Accounts\AccountsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class AccountTypesController extends Controller
{
    public function viewAny(): JsonResponse
    {
        $user = auth()->user();

        $accountTypes = AccountType::query()
            ->where('user_id', $user->id)
            ->get();

        return response()->json($accountTypes);
    }

    /** @throws ValidationException */
    public function save(
        AccountsService $accountsService,
        Request $request,
        ?AccountType $accountType = null
    ): JsonResponse {
        $this->validate($request, [
            'name' => 'required|exists:currencies,id',
        ]);

        $accountType = $accountsService->saveAccountType($request->name, $accountType);

        return response()->json($accountType);
    }

    public function delete(AccountsService $accountsService, AccountType $accountType): Response
    {
        $accountsService->deleteAccountType($accountType);

        return response()->noContent();
    }
}
