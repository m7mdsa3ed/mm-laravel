<?php

namespace App\Actions\Subscriptions;

use App\Actions\Action;
use App\Events\Subscriptions\SubscriptionCreatedEvent;
use App\Events\Subscriptions\SubscriptionUpdatedEvent;
use App\Models\Subscription;

class SubscriptionSavingAction extends Action
{
    private $event;

    public function __construct(
        private Subscription $subscription,
        private array $requestInputs = [],
    ) {
        $this->event = $subscription->id ? SubscriptionUpdatedEvent::class : SubscriptionCreatedEvent::class;
    }

    public function execute()
    {
        $fields = collect($this->requestInputs)
            ->only([
                'name',
                'amount',
                'account_id',
                'category_id',
                'interval_unit',
                'interval_count',
                'starts_at',
            ])
            ->toArray();

        $this->subscription->fill($fields);

        $this->subscription->user()->associate(auth()->id());

        $this->subscription->save();

        $eventClass = $this->event;

        event((new $eventClass($this->subscription)));

        return $this->subscription;
    }
}
