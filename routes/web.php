<?php

use App\Http\Controllers\SocialiteController;
use Illuminate\Http\Request;
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
        'name' => config('app.name'),
        'timezone' => config('app.timezone'),
        'current_time' => now()->format('Y-m-d H:i:s T'),
    ];
});

Route::get('phpinfo', fn () => phpinfo());

Route::get('config', fn () => config()->all());

Route::group(['prefix' => 'oauth2', 'as' => 'oauth.'], function () {
    Route::get('login/{provider}', [SocialiteController::class, 'url'])
        ->name('login');

    Route::get('login/{provider}/redirect', [SocialiteController::class, 'redirect'])
        ->name('redirect')
        ->middleware('signed');

    Route::get('login/{provider}/callback', [SocialiteController::class, 'callback'])
        ->name('callback');
});

Route::get('t/cache', function () {
    cache()->put('key', 'value', 60);

    return cache()->get('key');
});

Route::get('t', function (Request $request) {
    $processFactory = app(\Illuminate\Process\Factory::class);

    $pool = $processFactory->pool(function($pool) {
        $pool->path(base_path())->command('php artisan app:concurrency');
        $pool->path(base_path())->command('php artisan app:concurrency');
        $pool->path(base_path())->command('php artisan app:concurrency');
        $pool->path(base_path())->command('php artisan app:concurrency');
    });

    if ($request->boolean('w')) {
        return $pool->start()->wait()->collect()->map(function($result) {
            return json_decode($result->output(), true);
        });
    }

    $pool->start();

    return [
        'success' => true,
        'message' => 'Commands are running in the background.',
    ];
});
