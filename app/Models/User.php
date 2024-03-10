<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    public function mainCurrency(): HasOne
    {
        return $this->hasOne(Currency::class, 'id', 'currency_id');
    }

    public function currencyRates(): HasMany
    {
        return $this->hasMany(UserCurrencyRate::class);
    }

    public function currencies(): HasManyThrough
    {
        return $this->hasManyThrough(Currency::class, Account::class, 'user_id', 'id', 'id', 'currency_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function routeNotificationForWhatsApp()
    {
        return str_replace('+', '', $this->phone);
    }

    public function getMainCurrency(): Currency
    {
        static $currency = null;

        /** @var Currency $currency */
        $currency ??= $this->mainCurrency;

        return $currency;
    }

    public function currencySlugs(): array
    {
        $currencies = $this->currencies
            ->pluck('slug')
            ->unique();

        $xauKaratCurrencies = array_map(fn ($k) => 'XAU' . $k, Currency::supportedXauKarats());

        $currencies = $currencies->map(fn ($currency) => in_array($currency, $xauKaratCurrencies) ? 'XAU' : $currency);

        return $currencies
            ->unique()
            ->toArray();
    }
}
