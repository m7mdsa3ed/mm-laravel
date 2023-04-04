<?php

namespace App\Services\Socialite;

use App\Models\User;
use App\Services\App\AppService;
use App\Services\Users\UserService;
use App\Traits\HasInstanceGetter;
use Exception;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

class SocialiteService
{
    use HasInstanceGetter;

    /** @throws Exception */
    public function url(string $provider)
    {
        if (!$this->validateProvider($provider)) {
            throw new Exception('Provider ' . r($provider) . ' configs is missing.');
        }

        $this->overrideRedirectTo($provider);

        return url()
            ->signedRoute('oauth.redirect', $provider);
    }

    public function redirect(string $provider): RedirectResponse
    {
        return Socialite::driver($provider)
            ->redirect();
    }

    public function getUser(string $provider): User
    {
        $user = Socialite::driver($provider)
            ->user();

        return $this->getSocialiteUser($user, $provider);
    }

    public function validateProvider(string $provider): bool
    {
        $activeServices = AppService::getInstance()
            ->getServices();

        return in_array($provider, $activeServices);
    }

    private function overrideRedirectTo(string $provider): void
    {
        $redirectUrl = route('oauth.callback', $provider);

        config(["services.$provider.redirect" => $redirectUrl]);
    }

    public function getSocialiteUser(SocialiteUser $socialiteUser, string $provider): User
    {
        $userService = UserService::getInstance();

        $user = $userService->getByEmail($socialiteUser->getEmail());

        if ($user) {
            $userInputs = $this->getUserInputsFromSocialiteUser($socialiteUser);

            $user = $userService->createUser($userInputs);
        }

        $userService->saveOAuthProvider($user, $provider, $socialiteUser);

        return $user;
    }

    private function getUserInputsFromSocialiteUser(SocialiteUser $socialiteUser): array
    {
        return [
            // TODO Get User Attributes For Database Creation
        ];
    }
}
