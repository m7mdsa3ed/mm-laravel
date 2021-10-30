<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::post('export-json', function (Request $request) {

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

Route::post('import-json', function (Request $request) {

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
