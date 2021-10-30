<?php

use App\Models\Category;
use App\Models\Transaction;
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

Route::get('export-json', function (Request $request) {

    $tables = $request->tables ?? ['transactions', 'accounts', 'users', 'categories'];

    $backupsDir = env('JSON_BACKUP_DIR');

    if (!file_exists($backupsDir)) {
        mkdir($backupsDir, 0777, true);
    }

    foreach ($tables as $table) {
        $tableResults = DB::select('SELECT * FROM ' . $table);

        file_put_contents("$backupsDir/$table.json", json_encode($tableResults));
    }

    return collect($tables)->map(fn ($row) => url("$backupsDir/$row.json"));
});

Route::get('import-json', function (Request $request) {

    $arr = [
        'accounts'      => \App\Models\Account::class,
        'transactions'  => \App\Models\Transaction::class,
        'users'         => \App\Models\User::class,
        'categories'    => \App\Models\Category::class,
    ];

    foreach ($request->file('files') as $file) {

        $content = json_decode(file_get_contents($file->getPathName()), true);

        $tableName = pathinfo($file->getClientOriginalName())['filename'];

        $model = new $arr[$tableName];
        $model->truncate();
        $model->insert($content);

        if (config('database.default') === 'pgsql') {
            DB::select("SELECT setval('" . $tableName . "_id_seq', (SELECT MAX(id) from " . $tableName . "))");
        }
    }
});
