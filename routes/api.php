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

Route::get('stats', "GeneralController@stats");

Route::prefix('accounts')->group(function () {
    Route::get('', 'AccountsController@viewAny');
    Route::post('', 'AccountsController@save');
    Route::post('{account}/update', 'AccountsController@save');
    Route::post('{account}/delete', 'AccountsController@delete');
});

Route::prefix('categories')->group(function () {
    Route::get('', 'CategoriesController@viewAny');
    Route::post('', 'CategoriesController@save');
    Route::post('{category}/update', 'CategoriesController@save');
    Route::post('{category}/delete', 'CategoriesController@delete');
});

Route::prefix('transactions')->group(function () {
    Route::get('', 'TransactionsController@viewAny');
    Route::post('', 'TransactionsController@save');
    Route::post('{transaction}/update', 'TransactionsController@save');
    Route::post('{transaction}/delete', 'TransactionsController@delete');
});
