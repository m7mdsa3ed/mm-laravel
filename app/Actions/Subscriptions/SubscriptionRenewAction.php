<?php

namespace App\Actions\Subscriptions;

use App\Actions\Action;
use App\Enums\ActionEnum;
use App\Enums\ActionTypeEnum;
use App\Events\Subscriptions\SubscriptionRenewedEvent;
use App\Models\Subscription;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class SubscriptionRenewAction extends Action
{
    public function __construct(
        private Subscription $subscription,
        private array $options = [],
    ) {
        //
    }

    public function execute()
    {
        DB::transaction(function () {
            $this->createTransaction();

            $this->updateSubscription();

            event(new SubscriptionRenewedEvent($this->subscription));
        });

        return $this->subscription;
    }

    private function createTransaction()
    {
        // Create new transactions with subscription amount
        $transaction = new Transaction();

        $transaction->user()->associate(auth()->id());

        $fields = [
            'action' => ActionEnum::OUT(),
            'action_type' => ActionTypeEnum::OUTCOME(),
            'amount' => $this->subscription->amount,
            'account_id' => $this->subscription->account_id,
            'category_id' => $this->subscription->category_id,
            'description' => "Renewed {$this->subscription->name} subscription",
        ];

        $transaction->fill($fields);

        $transaction->save();
    }

    private function updateSubscription()
    {
        $this->subscription->update([
            'starts_at' => now()->format('Y-m-d'),
        ]);
    }
}
