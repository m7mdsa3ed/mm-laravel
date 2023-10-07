<?php

use App\Http\Controllers\AccountsController;
use App\Http\Controllers\AccountTypesController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\BudgetsController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\CurrenciesController;
use App\Http\Controllers\GeneralController;
use App\Http\Controllers\NotificationControler;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TagsController;
use App\Http\Controllers\TransactionsController;
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

    Route::get('notifications', [NotificationControler::class, 'notifications']);

    Route::post('deploy', [GeneralController::class, 'deploy'])
        ->middleware(['role:manager']);

    Route::post('download-db', [GeneralController::class, 'downloadDatabase'])
        ->middleware(['role:manager']);

    Route::prefix('settings')->group(function () {
        Route::get('', [SettingsController::class, 'viewAny']);

        Route::post('save', [SettingsController::class, 'save']);
    });

    Route::prefix('accounts')->group(function () {
        Route::pattern('account', '[0-9]+');

        Route::get('', [AccountsController::class, 'viewAny']);

        Route::post('', [AccountsController::class, 'save']);

        Route::get('{account}', [AccountsController::class, 'show']);

        Route::post('{account}/update', [AccountsController::class, 'save']);

        Route::post('{account}/delete', [AccountsController::class, 'delete']);

        Route::post('{account}/pin', [SettingsController::class, 'updatePinAccounts']);

        Route::prefix('account-types')->group(function () {
            Route::get('', [AccountTypesController::class, 'viewAny']);

            Route::post('save/{accountType?}', [AccountTypesController::class, 'save']);

            Route::post('delete/{accountType?}', [AccountTypesController::class, 'delete']);
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
        Route::get('', [CurrenciesController::class, 'viewAny']);

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
    });
});

Route::prefix('h')->group(function () {
    include 'helpers.php';
});
