<?php

namespace App\Http\Controllers;

use App\Services\Socialite\SocialiteService;
use Illuminate\Http\RedirectResponse;

class SocialiteController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        return SocialiteService::getInstance()
            ->redirect($provider);
    }

    public function callback(string $provider)
    {
        $user = SocialiteService::getInstance()
            ->getUser($provider);

        // TODO login with this user;
    }
}
