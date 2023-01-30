<?php

namespace App\Listeners;

use App\Events\Subscriptions\SubscriptionCreatedEvent;
use App\Events\Subscriptions\SubscriptionDeletedEvent;
use App\Events\Subscriptions\SubscriptionRenewedEvent;
use App\Events\Subscriptions\SubscriptionUpdatedEvent;
use App\Notifications\Subscriptions\SubscriptionRenewedNotification;
use Illuminate\Support\Facades\Notification;

class SubscriptionsListener
{
    private $eventsMap = [
        SubscriptionCreatedEvent::class => 'subscriptionCreated',
        SubscriptionUpdatedEvent::class => 'subscriptionUpdated',
        SubscriptionDeletedEvent::class => 'subscriptionDeleted',
        SubscriptionRenewedEvent::class => 'subscriptionRenewed',
    ];

    public function handle($event)
    {
        $eventName = get_class($event);

        $eventMethod = $this->eventsMap[$eventName] ?? null;

        if ($eventMethod && method_exists($this, $eventMethod)) {
            $this->$eventMethod($event->subscription);
        }
    }

    protected function subscriptionCreated($subscription)
    {
    }

    protected function subscriptionUpdated($subscription)
    {
    }

    protected function subscriptionDeleted($subscription)
    {
    }

    protected function subscriptionRenewed($subscription)
    {
        Notification::send($subscription->user, new SubscriptionRenewedNotification($subscription));
    }
}
