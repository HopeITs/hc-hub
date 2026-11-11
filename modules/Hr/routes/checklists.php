<?php

use Illuminate\Support\Facades\Route;

/*
| HR checklists: the items handed to an employee and taken back. The four
| lists are one page (hr::checklists.index) that reads which list to show
| from the route name; each route keeps the old page's own permission.
| Loaded by HrServiceProvider inside the web + auth + 2FA group.
*/
Route::prefix('hr/checklists')->name('hr.checklists.')->group(function (): void {
    Route::livewire('/', 'hr::checklists.index')
        ->can('checklists.view-own')
        ->name('index');

    Route::livewire('pending-delivers', 'hr::checklists.index')
        ->can('checklists.deliver')
        ->name('pending-delivers');

    Route::livewire('pending-returns', 'hr::checklists.index')
        ->can('checklists.receive')
        ->name('pending-returns');

    Route::livewire('done-requests', 'hr::checklists.index')
        ->can('checklists.manage')
        ->name('done-requests');

    Route::livewire('default-deliverables', 'hr::checklists.default-deliverables')
        ->can('checklists.manage')
        ->name('default-deliverables');
});
