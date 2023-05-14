<?php

namespace App\Notifications;

use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ResetPassword extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $token,
        private readonly ?string $redirectUrl = null,
    ) {

    }

    public function via(object $notifiable): array
    {
        return [
            WhatsAppChannel::class,
        ];
    }

    public function toWhatsApp(object $notifiable): array
    {
        return [
            'number' => $notifiable->routeNotificationForWhatsApp(),
            'message' => $this->getMessage(),
            'type' => 'link',
        ];
    }

    private function getMessage(): string
    {
        return $this->createRedirectUrl();
    }

    private function createRedirectUrl(): string
    {
        return $this->redirectUrl . '?token=' . $this->token;
    }
}
