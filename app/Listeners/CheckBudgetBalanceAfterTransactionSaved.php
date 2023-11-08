<?php

namespace App\Listeners;

use App\Enums\ActionEnum;
use App\Mail\GeneralMessageMail;
use App\Models\Budget;
use App\Models\User;
use App\Queries\BudgetsGetAllQuery;
use Illuminate\Support\Facades\Mail;
use Kreait\Firebase\Messaging\SendReport as FirebaseMessagingSendReport;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Throwable;

class CheckBudgetBalanceAfterTransactionSaved
{
    public function __construct()
    {
    }

    public function handle(object $event): void
    {
        /** @var \App\Models\Transaction $transaction */
        $transaction = $event->transaction;

        if ($transaction->action === ActionEnum::IN->value) {
            return;
        }

        $continue = $this->checkChanges($event->changes ?? []);

        if (!$continue) {
            return;
        }

        $user = $transaction->user;

        $mainCurrency = $user->getMainCurrency();

        $budgetsAlmostExceeded = $this->getBudgetsAlmostExceeded(
            $user->id,
            $mainCurrency->id
        );

        foreach ($budgetsAlmostExceeded as $budget) {
            $message = $this->getNotificationMessage($budget);

            $this->sendNotification($user, $message);
        }
    }

    private function getBudgetsAlmostExceeded(int $userId, int $currencyId)
    {
        $budgets = BudgetsGetAllQuery::get(
            userId: $userId,
            currencyId: $currencyId,
        );

        return $budgets->where('percentage', '>=', 80);
    }

    private function getNotificationMessage(Budget $budget): string
    {
        $budgetName = $budget->name;

        $budgetAmount = $budget->amount;

        $budgetPercentage = $budget->percentage;

        return "Your budget $budgetName is almost exceeded. You have spent $budgetPercentage% of $budgetAmount.";
    }

    private function sendNotification(mixed $user, string $message): void
    {
        $subject = 'Budget almost exceeded';

        Mail::to($user->email)
            ->queue(
                new GeneralMessageMail(
                    message: $message,
                    subject: $subject,
                )
            );

        $this->sendFcmNotification($user, $message, $subject);
    }

    private function sendFcmNotification(User $user, string $message, string $subject): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

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

    private function checkChanges(array $changes): bool
    {
        if (!count($changes)) {
            return false;
        }

        $listeners = [
            'amount',
            'category_id',
        ];

        return count(array_intersect($listeners, array_keys($changes)));
    }
}
