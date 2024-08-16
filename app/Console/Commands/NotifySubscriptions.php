<?php

namespace App\Console\Commands;

use App\Mail\GeneralMessageMail;
use App\Models\User;
use App\Services\Subscriptions\SubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Kreait\Firebase\Messaging\SendReport as FirebaseMessagingSendReport;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Throwable;
use Blade;

class NotifySubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notify-subscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /** Execute the console command. */
    public function handle(SubscriptionService $subscriptionService)
    {
        $subscriptionsByUsers = $subscriptionService
            ->getSubscriptionsThatAboutToExpire()
            ->groupBy('user_id');

        foreach ($subscriptionsByUsers as $subscriptions) {
            $user = $subscriptions->first()->user;

            foreach ($subscriptions as $subscription) {
                $this->sendNotification(
                    user: $user,
                    message:$this->generateMessage($subscription->toArray()),
                    subject: 'Subscription about to expire'
                );
            }
        }
    }

    private function generateMessage($subscription): string
    {
        $messages = [
            "Hello {{ \$name }}, just a heads up! Your '{{ \$subscription_name }}' subscription is set to expire on {{ \$expiry_date }}, in {{ \$remaining_days }} days. Don't miss out—make sure to renew it before it expires!",
            "Hi {{ \$name }}, your '{{ \$subscription_name }}' subscription is about to expire on {{ \$expiry_date }}. Only {{ \$remaining_days }} days left! Please take action now to ensure uninterrupted service.",
            "Reminder{{ \$ Your }} '{{ \$subscription_name }}' subscription will end on {{ \$expiry_date }}. You have {{ \$remaining_days }} days remaining. Consider renewing it to keep enjoying the benefits without interruption.",
        ];

        $message = $messages[array_rand($messages)];

        return Blade::render($message, [
            'name' => $subscription['user']['name'],
            'subscription_name' => $subscription['name'],
            'expiry_date' => $subscription['expires_at'],
            'remaining_days' => $subscription['remaining_days'],
        ], true);
    }

    private function sendNotification(User $user, string $message, string $subject): void
    {
        $this->sendEmail($user, $message, $subject);

        $this->sendFCM($user, $message, $subject);
    }

    private function sendEmail(User $user, string $message, string $subject): void
    {
        Mail::to($user->email)
            ->send(
                new GeneralMessageMail(
                    message: $message,
                    subject: $subject,
                )
            );
    }

    private function sendFCM(User $user, string $message, string $subject): void
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
        } catch (Throwable $th) {
            info('Can\'t send fcm notification', [
                'errorMessage' => $th->getMessage(),
                'user' => $user,
                'message' => $message,
            ]);
        }
    }
}
