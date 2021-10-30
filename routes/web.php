<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    return view('welcome');
});

Route::view('sql', 'sql');

Route::post('runSql', function (Request $request) {

    $sql = $request->sql;

    if ($sql) {

        try {

            $start = microtime(true);

            $response = DB::select($sql);

            $time = microtime(true) - $start;

            return response()->json([
                'data' => $response,
                'time' => $time
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 422);
        }
    }
})->name('sql');
