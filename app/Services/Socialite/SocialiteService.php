<?php

namespace App\Services\Socialite;

use App\Services\App\AppService;
use App\Traits\HasInstanceGetter;
use Exception;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

class SocialiteService
{
    use HasInstanceGetter;

    /** @throws Exception */
    public function url(string $provider): string
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

    public function getUser(string $provider): SocialiteUser
    {
        return Socialite::driver($provider)
            ->user();
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
}
