<?php

namespace App\Notifications;

use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

use Illuminate\Support\Facades\Blade;

class CurrencyTransferFeesNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly array $data
    ) {

    }

    public function via(object $notifiable): array
    {
        return [
            'database',
            WhatsAppChannel::class,
        ];
    }

    public function toWhatsApp(object $notifiable): array
    {
        return [
            'number' => $notifiable->routeNotificationForWhatsApp(),
            'message' => $this->getMessage(),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Currency Transfer Fees',
            'message' => $this->getMessage(),
            'data' => $this->data,
        ];
    }

    private function getMessage(): string
    {
        $string = 'Transfer fees for {{ money($from_amount, $from_currency) }} to {{ money($to_amount, $to_currency) }} is {{ money($fees_amount, $from_currency) }}';

        return Blade::render($string, $this->data);
    }
}
