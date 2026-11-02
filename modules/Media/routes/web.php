<?php

use Illuminate\Support\Facades\Route;
use Modules\Media\Http\Controllers\MediaRequestFileController;
use Modules\Media\Livewire\MediaRequests;

// Media requests. Required from routes/web.php inside the locale group and the
// 'auth' group: /{en|ar}/media-requests/…, route names media-requests.* — the
// same URLs and names as media-request, so its links and notifications carry over.

Route::prefix('media-requests')
    ->name('media-requests.')
    ->group(function () {
        Route::get('/home', MediaRequests\Dashboard::class)->middleware('active_permission:media.requests.view-own')->name('home');
        Route::get('/', MediaRequests\Index::class)->middleware('active_permission:media.requests.view-own')->name('index');
        Route::get('/approvals', MediaRequests\Approvals::class)->middleware('active_permission:media.requests.approve')->name('approvals');
        Route::get('/board', MediaRequests\Board::class)->middleware('active_permission:media.requests.work')->name('board');
        Route::get('/drive', MediaRequests\Drive::class)->middleware('active_permission:media.requests.view-own')->name('drive');
        Route::get('/stats', MediaRequests\Stats::class)->middleware('active_permission:media.requests.work')->name('stats');
        Route::get('/hc-pack', MediaRequests\Pack::class)->middleware('active_permission:media.requests.create')->name('pack');
        Route::get('/log', MediaRequests\Log::class)->middleware('active_permission:media.requests.view-log')->name('log');
        Route::get('/assistant', MediaRequests\AssistantLog::class)->middleware('active_permission:media.requests.view-log')->name('assistant');
        Route::get('/files/{media}', [MediaRequestFileController::class, 'download'])
            ->middleware(['active_permission:media.requests.view-own', 'throttle:60,1'])
            ->name('files');
        // A finished, approved design shown on everyone's home screen (the request itself stays private).
        Route::get('/showcase/{media}', [MediaRequestFileController::class, 'showcase'])
            ->middleware(['active_permission:media.requests.view-own', 'throttle:120,1'])
            ->name('showcase');
        Route::get('/{mediaRequest}', MediaRequests\Show::class)
            ->whereNumber('mediaRequest')
            ->middleware('active_permission:media.requests.view-own')
            ->name('show');
    });
