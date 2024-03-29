<?php

namespace App\Http\Controllers;

use App\Services\Socialite\SocialiteService;
use App\Services\Users\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;
use Exception;

class SocialiteController extends Controller
{
    /**
     * @param string $provider
     * @return JsonResponse
     */
    public function url(string $provider): JsonResponse
    {
        try {
            $url = SocialiteService::getInstance()
                ->url($provider);

            return response()->json([
                'url' => $url,
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function redirect(string $provider): RedirectResponse
    {
        return SocialiteService::getInstance()
            ->redirect($provider);
    }

    public function callback(string $provider): View
    {
        try {
            $socialiteUser = SocialiteService::getInstance()
                ->getUser($provider);

            $user = UserService::getInstance()
                ->getUserFromSocialUser($socialiteUser, $provider);

            $payload = UserService::getInstance()->createTokenResponse($user);
        } catch (Exception $e) {
            $payload = [
                'message' => $e->getMessage(),
            ];
        }

        return view('oauth2', [
            'payload' => $payload,
        ]);
    }
}
