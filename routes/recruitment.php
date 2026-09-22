<?php

use App\Livewire\Recruitment\Approvals\Pending;
use App\Livewire\Recruitment\Requests\Create;
use App\Livewire\Recruitment\Requests\Details;
use App\Livewire\Recruitment\Requests\Edit;
use App\Livewire\Recruitment\Requests\Index;
use Illuminate\Support\Facades\Route;

Route::prefix('recruitment/requests')->name('recruitment.requests.')
    ->middleware('active_permission:recruitment-requests.view')
    ->group(function () {
        Route::get('/', Index::class)->name('index');
        Route::get('/create', Create::class)->middleware('active_permission:recruitment-requests.manage')->name('create');
        Route::get('/{requestId}/edit', Edit::class)->whereNumber('requestId')->middleware('active_permission:recruitment-requests.manage')->name('edit');
        Route::get('/{requestId}', Details::class)->whereNumber('requestId')->name('show');
    });

Route::get('recruitment/approvals', Pending::class)
    ->middleware(['active_permission:recruitment-requests.approve'])
    ->name('recruitment.approvals.pending');
