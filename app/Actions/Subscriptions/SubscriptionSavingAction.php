<?php

namespace App\Actions\Subscriptions;

use App\Actions\Action;
use App\Models\Subscription;

class SubscriptionSavingAction extends Action
{
    private $event;

    public function __construct(
        private Subscription $subscription,
        private array $inputes = [],
    ) {
        $this->event = $subscription->id ? SubscriptionUpdatedEvent::class : SubscriptionCreatedEvent::class;
    }

    public function execute()
    {
        $fields = collect($this->inputs)
            ->only([
                'name',
                'amount',
                'expires_at',
                'auto_renewal',
            ])
            ->toArray();

        $this->subscription->fill($fields);

        $this->subscription->user()->associate(auth()->id());

        $this->subscription->save();

        event((new $this->event)($this->subscription));

        return $this->subscription;
    }
}
