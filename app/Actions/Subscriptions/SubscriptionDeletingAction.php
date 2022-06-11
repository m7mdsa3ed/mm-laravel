<?php

namespace App\Actions\Subscriptions;

use App\Actions\Action;
use App\Events\Subscriptions\SubscriptionDeletedEvent;
use App\Models\Subscription;

class SubscriptionDeletingAction extends Action
{
    public function __construct(
        private Subscription $subscription,
    ) {
        //
    }

    public function execute()
    {
        $this->subscription->delete();

        event(new SubscriptionDeletedEvent($this->subscription));
    }
}
