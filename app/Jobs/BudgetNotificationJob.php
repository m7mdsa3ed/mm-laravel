<?php

namespace App\Jobs;

use App\Actions\SendWhatsappMessage;
use App\Mail\GeneralMessageMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Kreait\Firebase\Messaging\SendReport as FirebaseMessagingSendReport;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Throwable;

class BudgetNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** Create a new job instance. */
    public function __construct(
        private readonly User $user,
        private readonly string $message,
    ) {
    }

    /** Execute the job. */
    public function handle(): void
    {
        $this->sendNotification($this->user, $this->message);
    }

    private function sendNotification(mixed $user, string $message): void
    {
        $subject = 'Budget almost exceeded';

        rawNotification($user->id, [
            'title' => $subject,
            'message' => $message,
        ]);

        $this->sendEmailNotification($user, $message, $subject);

        $this->sendFcmNotification($user, $message, $subject);

        $this->sendWhatsAppNotification($user, $message, $subject);
    }

    private function sendFcmNotification(User $user, string $message, string $subject): void
    {
        $firebaseMessaging = Firebase::messaging();

        $messages = $user->fcmTokens
            ->pluck('token')
            ->map(fn ($token) => [
                'token' => $token,
                'data' => [
                    'body' => $message,
                    'title' => $subject,
                    'icon' => 'favicon.ico',
                ],
            ]);

        try {
            $results = $firebaseMessaging->sendAll($messages);

            $toBeRemoved = $results
                ->failures()
                ->map(function (FirebaseMessagingSendReport $report) {
                    return $report->target()->value();
                });

            $user->fcmTokens()
                ->whereIn('token', $toBeRemoved)
                ->delete();
        } catch (Throwable) {
        }
    }

    private function sendEmailNotification($user, $message, $subject): void
    {
        return;

        Mail::to($user->email)
            ->send(
                new GeneralMessageMail(
                    message: $message,
                    subject: $subject,
                )
            );
    }

    private function sendWhatsAppNotification(mixed $user, string $message): void
    {
        return;

        try {
            $sender = SendWhatsappMessage::getInstance([
                'phoneNumber' => $user->phone,
                'message' => $message,
                'type' => 'Notification',
            ]);

            $sender->execute();
        } catch (Throwable) {

        }
    }
}
