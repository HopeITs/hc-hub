<?php

use Modules\Hr\Livewire\Position\PositionCreate;
use Modules\Hr\Livewire\Position\PositionEdit;
use Modules\Hr\Livewire\Position\PositionsModule;
use Modules\Hr\Livewire\ProgramManagement\Center\CenterCreate;
use Modules\Hr\Livewire\ProgramManagement\Center\CenterDetails;
use Modules\Hr\Livewire\ProgramManagement\Center\CentersModule;
use Modules\Hr\Livewire\ProgramManagement\Program\Create as ProgramCreate;
use Modules\Hr\Livewire\ProgramManagement\Program\Detail as ProgramDetail;
use Modules\Hr\Livewire\ProgramManagement\Program\Index as ProgramIndex;
use Modules\Hr\Livewire\ProgramManagement\Program\Update as ProgramUpdate;
use Illuminate\Support\Facades\Route;

Route::prefix('programs')
    ->name('programs.')
    ->group(function () {
        Route::get('/', ProgramIndex::class)->name('index')->middleware('reference_page:programs');
        Route::get('/create', ProgramCreate::class)->name('create')->middleware('active_permission:hr.programs.create');
        Route::get('/edit/{program:slug}', ProgramUpdate::class)->name('edit')->middleware('active_permission:hr.programs.update');
        Route::get('/{program:slug}', ProgramDetail::class)->name('show')->middleware('reference_page:programs');
    });
Route::prefix('positions')
    ->name('positions.')
    ->group(function () {
        Route::get('/', PositionsModule::class)->middleware('reference_page:positions')->name('index');
        Route::get('/create', PositionCreate::class)->middleware('active_permission:hr.positions.create')->name('create');
        Route::get('/{position}/edit', PositionEdit::class)->middleware('active_permission:hr.positions.update')->name('edit');
    });

Route::prefix('centers')
    ->name('centers.')
    ->group(function () {
        Route::get('/', CentersModule::class)->middleware('reference_page:centers')->name('index');
        Route::get('/create', CenterCreate::class)->name('create')->middleware('active_permission:hr.centers.create');
        Route::get('/{center}', CenterDetails::class)->middleware('reference_page:centers')->name('show');
    });
