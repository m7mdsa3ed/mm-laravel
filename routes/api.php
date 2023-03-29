<?php

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

Route::post('login', 'AuthenticationController@authenticate');

Route::post('register', 'AuthenticationController@register');

Route::get('appInfo', 'GeneralController@appInfo');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('me', 'AuthenticationController@me');

    Route::post('logout', 'AuthenticationController@unauthenticate');

    Route::get('stats', 'GeneralController@stats');

    Route::get('balance-details', 'GeneralController@getBalanceDetails');

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

    Route::prefix('currencies')->middleware(['role:manager'])->group(function () {
        Route::get('', 'CurrenciesController@viewAny');

        Route::post('update/{currency}', 'CurrenciesController@save');
    });

    Route::prefix('roles')->middleware(['role:manager'])->group(function () {
        Route::get('', 'RolesController@viewAny')->name('roles.viewAny');

        Route::post('syncRoles', 'RolesController@syncRoles');
    });
});

Route::prefix('h')->group(function () {
    include 'helpers.php';
});
