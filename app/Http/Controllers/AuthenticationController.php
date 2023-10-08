<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\ResetPassword;
use App\Services\Users\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
            'phone' => 'sometimes|regex:/^\+[1-9]\d{1,14}$/|nullable',
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

    private function createTokenResponse(User $user): array
    {
        return UserService::getInstance()->createTokenResponse($user);
    }

    public function forgetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Validate if the email exists in the database
        $user = User::where('email', $request->email)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages(['email' => 'Email not found']);
        }

        $token = Str::random(64);

        DB::table(config('auth.passwords.users.table'))->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => now(),
        ]);

        $user->notify(new ResetPassword($token, $request->redirectUrl));

        return response()
            ->json([
                'message' => 'Password reset link sent.',
            ]);
    }

    /** @throws ValidationException */
    public function resetPassword(Request $request): JsonResponse
    {
        $this->validate($request, [
            'token' => 'required',
            'password' => 'required',
        ]);

        $resetPasswordRecord = DB::table(config('auth.passwords.users.table'))
            ->where('token', $request->token)
            ->first();

        if (!$resetPasswordRecord) {
            throw ValidationException::withMessages(['token' => 'Invalid token']);
        }

        $user = User::where('email', $resetPasswordRecord->email)
            ->first();

        $user->update([
            'password' => bcrypt($request->password),
        ]);

        DB::table(config('auth.passwords.users.table'))
            ->where('token', $resetPasswordRecord->token)
            ->delete();

        return response()->json($this->createTokenResponse($user));
    }
}
