<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use Notifiable;
    use HasRoles;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(Settings::class);
    }

    public function oauthProviders(): HasMany
    {
        return $this->hasMany(UserOAuthProvider::class);
    }

    public function fcmTokens(): HasMany
    {
        return $this->hasMany(UserFcmToken::class);
    }

    public function currencyRates(): HasMany
    {
        return $this->hasMany(UserCurrencyRate::class);
    }

    public function routeNotificationForWhatsApp()
    {
        return str_replace('+', '', $this->phone);
    }

    public function getMainCurrency(): Currency
    {
        static $currency = null;

        /** @var Currency $currency */
        $currency ??= Currency::query()
            ->where('name', 'EGP')
            ->first();

        return $currency;
    }

    public function currencies(): HasManyThrough
    {
        return $this->hasManyThrough(Currency::class, Account::class, 'user_id', 'id', 'id', 'currency_id');
    }
}
