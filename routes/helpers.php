<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

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

        Schema::disableForeignKeyConstraints();

        $model->truncate();
        $model->insert($content);

        if (config('database.default') === 'pgsql') {
            DB::select("SELECT setval('" . $tableName . "_id_seq', (SELECT MAX(id) from " . $tableName . "))");
        }

        Schema::enableForeignKeyConstraints();
    }
});

Route::get('backup', function() {
    $files = Storage::allFiles(env('APP_NAME'));

    $files = array_map( fn ($file) => [
        'name' => $file,
        'downloadLink' => url()->signedRoute('download', [
            'path' => $file
        ])
    ], $files);

    return response()->json([
        'message' => 'Backup Files',
        'backupNow' => route('artisan', ['command' => 'backup:run --only-db']),
        'data' => $files
    ]);
});

Route::get('/artisan', function () {
    $command = request('command') ?? 'migrate';

    Artisan::call($command);

    $output = Artisan::output();

    $output = explode("\r\n", $output);

    return response()->json([
        'output' => $output 
    ]);
})->name('artisan');

Route::get('/download', function() {
    $relativePath = request('path');
    
    return response()->download($relativePath);
})->name('download')->middleware('signed');
