<?php

use Illuminate\Support\Facades\Route;
use Modules\Hr\Http\Controllers\Employees\ArchiveFileController;
use Modules\Hr\Http\Controllers\Employees\EmployeeFileController;

/*
| Employees: the directory, a profile, the record form, badges and the
| archive folders. Loaded by HrServiceProvider inside web + auth + 2FA.
*/
Route::prefix('hr')->name('hr.')->group(function (): void {
    Route::livewire('employees', 'hr::employees.index')
        ->can('employees.view')
        ->name('employees.index');

    Route::livewire('employees/create', 'hr::employees.form')
        ->can('employees.create')
        ->name('employees.create');

    Route::livewire('employees/{employee}/edit', 'hr::employees.form')
        ->whereNumber('employee')
        ->can('employees.update')
        ->name('employees.edit');

    // Open to everyone: the page itself decides what the viewer may see
    // (EmployeePolicy::viewDirectory, viewRecords, viewPrivateDocuments).
    Route::livewire('employees/{employee:username}/show', 'hr::employees.show')
        ->name('employees.show');

    Route::livewire('my-profile', 'hr::employees.show')
        ->name('employees.me');

    Route::get('employee-files/{media}/download', EmployeeFileController::class)
        ->whereNumber('media')
        ->name('employees.files.download');

    Route::livewire('badges', 'hr::employees.badges')
        ->can('badges.view')
        ->name('badges.index');

    Route::livewire('my-folders/{folder?}', 'hr::employees.archives')
        ->whereNumber('folder')
        ->can('archives.view')
        ->name('archives.mine');

    Route::livewire('shared-folders/{folder?}', 'hr::employees.archives')
        ->whereNumber('folder')
        ->can('archives.view')
        ->name('archives.shared');

    Route::get('folder-files/{media}/preview', [ArchiveFileController::class, 'preview'])
        ->whereNumber('media')
        ->can('archives.view')
        ->name('archives.files.preview');

    Route::get('folder-files/{media}/download', [ArchiveFileController::class, 'download'])
        ->whereNumber('media')
        ->can('archives.download')
        ->name('archives.files.download');
});
