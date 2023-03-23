<?php

use App\Http\Controllers\SocialiteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return [
        'name' => env('APP_NAME'),
    ];
});

Route::group(['prefix', 'oauth2', 'as' => 'oauth.'], function () {
    Route::get('login/{provider}', [SocialiteController::class, 'url'])
        ->name('login');

    Route::get('login/{provider}/redirect', [SocialiteController::class, 'redirect'])
        ->name('redirect')
        ->middleware('signed');

    Route::get('login/{provider}/callback', [SocialiteController::class, 'callback'])
        ->name('redirect');
});
