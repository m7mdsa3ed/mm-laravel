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

Route::get('phpinfo', fn () => phpinfo());

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

function parseCSVWithHeadersAndMerge($filePath, ?callable $rowCallback = null, ?callable $headerCallback = null): false|array
{
    $csvFile = fopen($filePath, 'r');

    if ($csvFile !== false) {
        $firstLine = fgets($csvFile);

        if (str_starts_with($firstLine, "\u{FEFF}")) {
            $firstLine = substr($firstLine, 3);
        }

        $headers = str_getcsv($firstLine);

        $headers = $headerCallback ? call_user_func($headerCallback, $headers) : $headers;

        $r = [];

        while (($data = fgetcsv($csvFile)) !== false) {
            $rowData = array_combine($headers, $data);

            $r[] = $rowCallback ? call_user_func($rowCallback, $rowData) : $rowData;
        }

        fclose($csvFile);

        return $r;
    } else {
        return false;
    }
}

Route::get('t', function () {
    $filePath = Storage::path('file.csv');

    return parseCSVWithHeadersAndMerge($filePath);
});
