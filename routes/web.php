<?php

use Cruxinator\Attachments\Http\Controllers\DownloadController;
use Cruxinator\Attachments\Http\Controllers\DropzoneController;
use Cruxinator\Attachments\Http\Controllers\ShareController;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => Config::get('attachments.routes.prefix'),
    'middleware' => Config::get('attachments.routes.middleware'),
], function () {
    $validID = '[a-zA-Z0-9-]+';
    $validName = '.+';
    Route::get(Config::get('attachments.routes.shared_pattern'), [ShareController::class, 'download'])
        ->where('token', $validName)
        ->where('id', $validID)
        ->where('name', $validName)
        ->name('attachments.download-shared');
    Route::get(Config::get('attachments.routes.pattern'), [DownloadController::class, 'download'])
        ->where('id', $validID)
        ->where('name', $validName)
        ->name('attachments.download');

    Route::post(Config::get('attachments.routes.dropzone.upload_pattern'), [DropzoneController::class, 'post'])
        ->name('attachments.dropzone');

    Route::delete(Config::get('attachments.routes.dropzone.delete_pattern'), [DropzoneController::class, 'delete'])
        ->where('id', $validID)
        ->where('name', $validName)
        ->name('attachments.dropzone.delete');
});
