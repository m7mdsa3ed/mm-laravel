<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

Route::get('backup', function() {
    $files = Storage::allFiles(env('APP_NAME'));

    $files = array_filter($files, fn ($file) => pathinfo($file)['extension'] === 'zip');

    $files = array_values($files);

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

    $absPath = Storage::path($relativePath);

    return response()->download($absPath);
})->name('download')->middleware('signed');
