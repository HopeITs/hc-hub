<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
| Requests and approvals. Each page checks its own `recruitment-requests.*`
| permission against the active role (the old `active_permission:` guard)
| and asks the request policy, so a guessed id outside the viewer's scope is
| a 404.
*/

Route::prefix('hr/recruitment/requests')->name('hr.recruitment.requests.')->group(function (): void {
    Route::livewire('/', 'hr::recruitment.requests.index')->name('index');
    Route::livewire('/team', 'hr::recruitment.requests.team')->name('team');
    Route::livewire('/create', 'hr::recruitment.requests.create')->name('create');
    // The shortcut is behind its own permission, never implied by manage.
    Route::livewire('/direct-open', 'hr::recruitment.requests.direct-open')->name('direct-open');
    Route::livewire('/{requestId}/edit', 'hr::recruitment.requests.edit')->whereNumber('requestId')->name('edit');
    Route::livewire('/{requestId}', 'hr::recruitment.requests.show')->whereNumber('requestId')->name('show');
});

Route::livewire('hr/recruitment/approvals', 'hr::recruitment.approvals.pending')->name('hr.recruitment.approvals.pending');
