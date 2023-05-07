<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticationController extends Controller
{
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, !!$request->remember)) {
            $response = $this->createTokenResponse($this->me());

            return response()->json($response);
        }

        return response()->json(['message' => 'The provided credentials do not match our records.'], 422);
    }

    /** @throws ValidationException */
    public function register(Request $request): JsonResponse
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'phone' => 'sometimes|regex:/^\+[1-9]\d{1,14}$/',
        ]);

        $inputs = [
            'name' => $request->name,
            'password' => bcrypt($request->password),
            'email' => $request->email,
            'phone' => $request->phone,
        ];

        $user = User::query()
            ->create($inputs);

        if ($user) {
            return response()->json($this->createTokenResponse($user));
        }

        return response()->json(['message' => 'Could not create.'], 422);
    }

    public function unauthenticate(Request $request)
    {
        $user = $request->user();

        if ($request->filled('all')) {
            $user->tokens()->delete();
        } else {
            $user->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Logged out.']);
    }

    public function me()
    {
        $user = auth()->user();

        return $user->load([
            'roles.permissions',
            'settings',
        ]);
    }

    private function createTokenResponse(User $user)
    {
        return [
            'token' => $user->createToken('ACCESS_TOKEN')->plainTextToken,
            'user' => $user,
        ];
    }
}
