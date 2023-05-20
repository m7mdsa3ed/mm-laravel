<?php

namespace App\Services\Users;

use App\Models\User;
use App\Models\UserOAuthProvider;
use App\Traits\HasInstanceGetter;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class UserService
{
    use HasInstanceGetter;

    public function createTokenResponse(User $user): array
    {
        return [
            'token' => $user->createToken('ACCESS_TOKEN')->plainTextToken,
            'user' => $user,
        ];
    }

    public function getUserFromSocialUser(SocialiteUser $socialiteUser, string $provider): User
    {
        $userOAuthProvider = UserOAuthProvider::query()
            ->where('provider', $provider)
            ->where('provider_user_id', $socialiteUser->getId())
            ->with('user')
            ->first();

        if ($userOAuthProvider) {
            $userOAuthProvider->update([
                'access_token' => $socialiteUser->token,
                'refresh_token' => $socialiteUser->refreshToken ?? null,
            ]);

            return $userOAuthProvider->user;
        }

        return $this->createUserFromSocialite($socialiteUser, $provider);
    }

    public function createUserFromSocialite(SocialiteUser $socialiteUser, string $provider): User
    {
        $user = User::query()
            ->updateOrCreate([
                'email' => $socialiteUser->getEmail(),
            ], [
                'name' => $socialiteUser->getName(),
            ]);

        $user->oauthProviders()
            ->updateOrCreate([
                'provider' => $provider,
                'provider_user_id' => $socialiteUser->getId(),
            ], [
                'access_token' => $socialiteUser->token,
                'refresh_token' => $socialiteUser->refreshToken ?? null,
            ]);

        return $user;
    }
}
