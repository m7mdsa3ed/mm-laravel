<?php

namespace App\Notifications\Channels;

use App\Actions\SendWhatsappMessage;
use Exception;
use Illuminate\Notifications\Notification;
use Log;

class WhatsAppChannel
{
    /**
     * Send the given notification.
     * @throws Exception
     */
    public function send(object $notifiable, Notification $notification): void
    {
        try {
            $payload = $notification->toWhatsApp($notifiable);

            if (!isset($payload['number'], $payload['message'])) {
                throw new Exception('The number and message are required');
            }

            $phoneNumber = $payload['number'];

            $message = $payload['message'];

            $this->sendMessage($phoneNumber, $message);
        } catch (Exception $e) {
            Log::error('Error sending WhatsApp message: '. $e->getMessage());
        }
    }

    /** @throws Exception */
    private function sendMessage(string $phoneNumber, string $message): void
    {
        $sender = SendWhatsappMessage::getInstance([
            'phoneNumber' => $phoneNumber,
            'message' => $message,
        ]);

        $sender->execute();
    }
}
