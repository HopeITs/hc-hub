<?php


use App\Livewire\Position\{PositionCreate, PositionEdit, PositionsModule};
use App\Livewire\ProgramManagement\{Center\CenterCreate,
    Center\CenterDetails,
    Center\CentersModule,
    Program\Create as ProgramCreate,
    Program\Detail as ProgramDetail,
    Program\Index as ProgramIndex,
    Program\Update as ProgramUpdate
};
use Illuminate\Support\Facades\Route;

Route::prefix('programs')
    ->name('programs.')
    ->group(function () {
        Route::get('/', ProgramIndex::class)->name('index')->middleware('role:HR|super-admin|manager|general-manager');
        Route::get('/create', ProgramCreate::class)->name('create')->middleware('role:HR|super-admin|general-manager');
        Route::get('/edit/{program:slug}', ProgramUpdate::class)->name('edit')->middleware('role:HR|super-admin|general-manager');
        Route::get('/{program:slug}', ProgramDetail::class)->name('show')->middleware('role:HR|super-admin|manager|general-manager');
    });
Route::prefix('positions')
    ->name('positions.')
    ->middleware('role:HR|super-admin')
    ->group(function () {
        Route::get('/', PositionsModule::class)->name('index');
        Route::get('/create', PositionCreate::class)->name('create');
        Route::get('/{position}/edit', PositionEdit::class)->name('edit');
    });


Route::prefix('centers')
    ->name('centers.')
    ->group(function () {
        Route::get('/', CentersModule::class)->name('index');
        Route::get('/create', CenterCreate::class)->name('create')->middleware('role:HR|super-admin');
        Route::get('/{center}', CenterDetails::class)->name('show');
    });













