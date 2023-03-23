<?php

namespace App\Services\Users;

use App\Models\User;
use App\Traits\HasInstanceGetter;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class UserService
{
    use HasInstanceGetter;

    /** @noinspection PhpUndefinedMethodInspection */
    public function createUser(array $userInputs): User
    {
        $userInputs = $this->validateUserInputs($userInputs);

        return User::updateOrCreate(['email' => $userInputs['email']], $userInputs);
    }

    private function validateUserInputs(array $userInputs): array
    {
        return $userInputs;
    }

    public function getByEmail(string $email): mixed
    {
        return User::where('email', $email)->first();
    }

    public function saveOAuthProvider(User $user, string $provider, SocialiteUser $socialiteUser)
    {
        // TODO Create OAuth Provider Response
    }
}
