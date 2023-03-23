<?php

namespace App\Providers;

use App\Events\Subscriptions\SubscriptionCreatedEvent;
use App\Events\Subscriptions\SubscriptionDeletedEvent;
use App\Events\Subscriptions\SubscriptionRenewedEvent;
use App\Listeners\SubscriptionsListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use SocialiteProviders\GitHub\GitHubExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        'Illuminate\Http\Client\Events\RequestSending' => [
            'App\Listeners\HttpListener',
        ],
        'Illuminate\Http\Client\Events\ResponseReceived' => [
            'App\Listeners\HttpListener',
        ],
        'Illuminate\Http\Client\Events\ConnectionFailed' => [
            'App\Listeners\HttpListener',
        ],

        SubscriptionCreatedEvent::class => [
            SubscriptionsListener::class,
        ],

        SubscriptionUpdatedEvent::class => [
            SubscriptionsListener::class,
        ],

        SubscriptionDeletedEvent::class => [
            SubscriptionsListener::class,
        ],

        SubscriptionRenewedEvent::class => [
            SubscriptionsListener::class,
        ],

        SocialiteWasCalled::class => [
            GitHubExtendSocialite::class . '@handle',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
    }
}
