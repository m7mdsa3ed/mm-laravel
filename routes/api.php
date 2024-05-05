<?php

use App\Http\Controllers\AccountCardsController;
use App\Http\Controllers\AccountsController;
use App\Http\Controllers\AccountTypesController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\BudgetsController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\ContactsController;
use App\Http\Controllers\CurrenciesController;
use App\Http\Controllers\GeneralController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasskeysController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TagsController;
use App\Http\Controllers\TransactionsController;
use App\Http\Controllers\UserCurrencyRatesController;
use App\Http\Controllers\WebhookQueueController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/', function () {
    return [
        'message' => 'Hello World!',
    ];
});

Route::post('login', [AuthenticationController::class, 'authenticate']);

Route::post('register', [AuthenticationController::class, 'register']);

Route::post('forget-password', [AuthenticationController::class, 'forgetPassword']);

Route::post('reset-password', [AuthenticationController::class, 'resetPassword']);

Route::get('appInfo', [GeneralController::class, 'appInfo']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('me', [AuthenticationController::class, 'me']);

    Route::post('logout', [AuthenticationController::class, 'unauthenticate']);

    Route::get('stats', [GeneralController::class, 'stats']);

    Route::get('balance-details', [GeneralController::class, 'getBalanceDetails']);

    Route::any('estimate', [GeneralController::class, 'getEstimate']);

    Route::get('notifications', [NotificationController::class, 'notifications']);

    Route::post('deploy', [GeneralController::class, 'deploy'])
        ->middleware(['role:manager']);

    Route::post('download-db', [GeneralController::class, 'downloadDatabase'])
        ->middleware(['role:manager']);

    Route::prefix('settings')->group(function () {
        Route::get('', [SettingsController::class, 'viewAny']);

        Route::post('save', [SettingsController::class, 'save']);

        Route::post('saveFcmToken', [SettingsController::class, 'saveFcmToken']);
    });

    Route::prefix('accounts')->group(function () {
        Route::pattern('account', '[0-9]+');

        Route::get('', [AccountsController::class, 'viewAny']);

        Route::post('', [AccountsController::class, 'save']);

        Route::get('{account}', [AccountsController::class, 'show']);

        Route::post('{account}/update', [AccountsController::class, 'save']);

        Route::post('{account}/delete', [AccountsController::class, 'delete']);

        Route::get('{account}/summary', [AccountsController::class, 'summary']);

        Route::post('{account}/pin', [SettingsController::class, 'updatePinAccounts']);

        Route::prefix('account-types')->group(function () {
            Route::get('', [AccountTypesController::class, 'viewAny']);

            Route::post('save/{accountType?}', [AccountTypesController::class, 'save']);

            Route::post('delete/{accountType}', [AccountTypesController::class, 'delete']);
        });

        Route::prefix('account-cards')->group(function () {
            Route::get('{accountId}', [AccountCardsController::class, 'viewAny']);

            Route::get('getOne/{id}', [AccountCardsController::class, 'viewOne'])
                ->middleware('passkey');

            Route::post('save/{accountCard?}', [AccountCardsController::class, 'save']);

            Route::post('delete/{accountCard}', [AccountCardsController::class, 'delete']);
        });
    });

    Route::prefix('categories')->group(function () {
        Route::get('', [CategoriesController::class, 'viewAny']);

        Route::post('', [CategoriesController::class, 'save']);

        Route::post('{category}/update', [CategoriesController::class, 'save']);

        Route::post('{category}/delete', [CategoriesController::class, 'delete']);

        Route::get('{category}/details', [CategoriesController::class, 'details']);
    });

    Route::prefix('tags')->group(function () {
        Route::get('', [TagsController::class, 'viewAny']);

        Route::post('', [TagsController::class, 'save']);

        Route::post('{tag}/update', [TagsController::class, 'save']);

        Route::post('{tag}/delete', [TagsController::class, 'delete']);
    });

    Route::prefix('transactions')->group(function () {
        Route::get('', [TransactionsController::class, 'viewAny']);

        Route::post('', [TransactionsController::class, 'save']);

        Route::post('{transaction}/update', [TransactionsController::class, 'save']);

        Route::post('{transaction}/delete', [TransactionsController::class, 'delete']);

        Route::post('move', [TransactionsController::class, 'moveMoney']);
    });

    Route::prefix('currencies')->group(function () {
        Route::get('', [CurrenciesController::class, 'viewAny'])->withoutMiddleware('auth:sanctum');

        Route::post('saveUserCurrencyRate/{currencyRateId}', [UserCurrencyRatesController::class, 'save']);

        Route::post('resetUserCurrencyRate/{id}', [UserCurrencyRatesController::class, 'delete']);

        Route::get('userCurrenciesWithRates', [CurrenciesController::class, 'getUserCurrenciesWithRates'])
            ->middleware('role:manager');

        Route::post('update/{currency}', [CurrenciesController::class, 'save'])
            ->middleware('role:manager');

        Route::post('update/{currencyRate}/rate', [CurrenciesController::class, 'updateRate'])
            ->middleware('role:manager');
    });

    Route::prefix('roles')->middleware(['role:manager'])->group(function () {
        Route::get('', [RolesController::class, 'viewAny'])->name('roles.viewAny');

        Route::post('syncRoles', [RolesController::class, 'syncRoles']);
    });

    Route::prefix('budgets')->group(function () {
        Route::get('', [BudgetsController::class, 'viewAny']);

        Route::post('', [BudgetsController::class, 'save']);

        Route::post('{budget}/update', [BudgetsController::class, 'save']);

        Route::post('{budget}/delete', [BudgetsController::class, 'delete']);

        Route::post('averageAmount', [BudgetsController::class, 'averageAmount']);
    });

    Route::prefix('passkeys')->group(function () {
        Route::get('', [PasskeysController::class, 'viewAny']);

        Route::any('createArguments', [PasskeysController::class, 'createArguments']);

        Route::any('getArguments', [PasskeysController::class, 'getArguments']);

        Route::any('createProcess', [PasskeysController::class, 'createProcess']);

        Route::any('getProcess', [PasskeysController::class, 'getProcess']);

        Route::any('refreshCertificates', [PasskeysController::class, 'refreshCertificates']);

        Route::post('{id}/delete', [PasskeysController::class, 'delete']);
    });

    Route::prefix('notifications')->group(function () {
        Route::get('', [NotificationController::class, 'notifications']);

        Route::post('markAsRead/{id?}', [NotificationController::class, 'markAsRead']);
    });

    Route::prefix('subscriptions')->group(function () {
        Route::get('', [SubscriptionController::class, 'viewAny']);

        Route::post('', [SubscriptionController::class, 'saveSubscription']);

        Route::post('{subscription}/update', [SubscriptionController::class, 'saveSubscription']);

        Route::post('{subscription}/delete', [SubscriptionController::class, 'deleteSubscription']);

        Route::post('{subscription}/renew', [SubscriptionController::class, 'renewSubscription']);

        Route::post('{subscription}/cancel', [SubscriptionController::class, 'cancelSubscription']);

        Route::post('{subscription}/reactivate', [SubscriptionController::class, 'reactivateSubscription']);
    });

    Route::prefix('contacts')->group(function () {
        Route::get('', [ContactsController::class, 'viewAny']);

        Route::post('', [ContactsController::class, 'saveContact']);

        Route::post('{subscription}/update', [ContactsController::class, 'saveContact']);

        Route::post('{subscription}/delete', [ContactsController::class, 'deleteContact']);
    });
});

Route::prefix('webhooks')->group(function () {
    Route::post('queue-handler', [WebhookQueueController::class, 'handle']);
});

Route::any('call/{artisanCommandName}', function ($artisanCommandName) {
    $presets = [
        'schedule' => 'schedule:run',
    ];

    $artisanCommandName = $presets[$artisanCommandName] ?? null;

    if ($artisanCommandName) {
        Artisan::call($artisanCommandName);

        $output = Artisan::output();

        $output = explode("\r\n", $output);

        return response()->json([
            'output' => $output,
        ]);
    }
});

Route::prefix('h')->group(function () {
    include 'helpers.php';
});

Route::get('t/dynamicSearch', function () {
    $term = request('term');

    liveResponse(function () use ($term) {
        $transactions = App\Models\Transaction::query()
            ->where('description', 'like', "%{$term}%")
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        if ($transactions->isNotEmpty()) {
            sendLiveResponse($transactions->map(fn ($transaction) => [
                'name' => 'Transaction #' . $transaction->id,
                'description' => $transaction->description,
                'type' => 'transaction',
                'details' => [
                    'transactionId' => $transaction->id,
                ],
            ]));
        }

        $accounts = App\Models\Account::query()
            ->where('name', 'like', "%{$term}%")
            ->orderBy('name', 'asc')
            ->limit(3)
            ->get();

        if ($accounts->isNotEmpty()) {
            sendLiveResponse($accounts->map(fn ($account) => [
                'name' => $account->name,
                'description' => $account->description,
                'type' => 'account',
                'details' => [
                    'accountId' => $account->id,
                ],
            ]));
        }

        $categories = App\Models\Category::query()
            ->where('name', 'like', "%{$term}%")
            ->orderBy('name', 'asc')
            ->limit(3)
            ->get();

        if ($categories->isNotEmpty()) {
            sendLiveResponse($categories->map(fn ($category) => [
                'name' => $category->name,
                'description' => $category->description,
                'type' => 'category',
                'details' => [
                    'categoryId' => $category->id,
                ],
            ]));
        }

        $contacts = App\Models\Contact::query()
            ->where('name', 'like', "%{$term}%")
            ->orderBy('name', 'asc')
            ->limit(3)
            ->get();

        if ($contacts->isNotEmpty()) {
            sendLiveResponse($contacts->map(fn ($contact) => [
                'name' => $contact->name,
                'description' => $contact->description,
                'type' => 'contact',
                'details' => [
                    'contactId' => $contact->id,
                ],
            ]));
        }

        $tags = App\Models\Tag::query()
            ->where('name', 'like', "%{$term}%")
            ->orderBy('name', 'asc')
            ->limit(3)
            ->get();

        if ($tags->isNotEmpty()) {
            sendLiveResponse($tags->map(fn ($tag) => [
                'name' => $tag->name,
                'description' => $tag->description,
                'type' => 'tag',
                'details' => [
                    'tagId' => $tag->id,
                ],
            ]));
        }
    });
});
