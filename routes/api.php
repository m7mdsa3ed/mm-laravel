<?php

use Illuminate\Support\Facades\Auth;
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

Route::get('', function () {
    return [
        'laravel_version' => app()->version(),
        'api_version' => 1
    ];
});

Route::post('login', "AuthenticationController@authenticate");
Route::post('register', "AuthenticationController@register");

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('me', fn () => Auth::user());
    Route::post('logout', "AuthenticationController@unauthenticate");

    Route::get('stats', "GeneralController@stats");

    Route::prefix('accounts')->group(function () {
        Route::get('', 'AccountsController@viewAny');
        Route::post('', 'AccountsController@save');
        Route::get('{account}', 'AccountsController@show');
        Route::post('{account}/update', 'AccountsController@save');
        Route::post('{account}/delete', 'AccountsController@delete');
    });

    Route::prefix('categories')->group(function () {
        Route::get('', 'CategoriesController@viewAny');
        Route::post('', 'CategoriesController@save');
        Route::post('{category}/update', 'CategoriesController@save');
        Route::post('{category}/delete', 'CategoriesController@delete');
    });

    Route::prefix('tags')->group(function () {
        Route::get('', 'TagsController@viewAny');
        Route::post('', 'TagsController@save');
        Route::post('{tag}/update', 'TagsController@save');
        Route::post('{tag}/delete', 'TagsController@delete');
    });

    Route::prefix('transactions')->group(function () {
        Route::get('', 'TransactionsController@viewAny');
        Route::post('', 'TransactionsController@save');
        Route::post('{transaction}/update', 'TransactionsController@save');
        Route::post('{transaction}/delete', 'TransactionsController@delete');

        Route::post('move', 'TransactionsController@moveMoney');
    });

    Route::get('currencies', "CurrenciesController@viewAny");

    Route::prefix('subscriptions')->group(function () {
        Route::get('', 'SubscriptionsController@viewAny');
        Route::post('', 'SubscriptionsController@save');
        Route::post('{subscription}/update', 'SubscriptionsController@save');
        Route::post('{subscription}/delete', 'SubscriptionsController@delete');
        Route::post('{subscription}/renew', 'SubscriptionsController@renew');
    });
});

Route::prefix('h')->group(function () {
    include 'helpers.php';
});
