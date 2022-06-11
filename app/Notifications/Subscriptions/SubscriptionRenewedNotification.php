<?php

namespace App\Notifications\Subscriptions;

use App\Mail\SubscriptionsRenewedMail;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubscriptionRenewedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Subscription $subscription
    ) {
        $this->afterCommit();
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $mailable = new SubscriptionsRenewedMail($this->subscription);

        $mailable->to($notifiable->email);

        return $mailable;
    }
}
