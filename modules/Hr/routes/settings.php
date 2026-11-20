<?php

use Illuminate\Support\Facades\Route;
use Modules\Hr\Support\Settings\ReferenceTables;

/*
| HR settings: the reference tables (one page, a tab each) and HR's
| read-only view of a program's staffing and of the Core centers. Loaded by
| HrServiceProvider inside the auth + 2FA group.
*/
Route::livewire('hr/settings', 'hr::settings.index')
    ->can('project-basics.view')
    ->name('hr.settings.index');

// The old app's one-page-per-table URLs open the matching tab.
foreach (['cities', ...ReferenceTables::keys()] as $tab) {
    Route::redirect('hr/settings/'.$tab, '/hr/settings'.($tab === 'cities' ? '' : '?tab='.$tab))
        ->name('hr.settings.'.$tab);
}

// One programs list for the hub (roadmap 3.6): HR's read-only copy leads to it, its name kept for old links.
Route::redirect('hr/programs', '/programs', 301)
    ->name('hr.settings.programs.index');

Route::livewire('hr/programs/{program}', 'hr::settings.program')
    ->can('programs.view')
    ->name('hr.settings.programs.show');

Route::livewire('hr/centers', 'hr::settings.centers')
    ->can('centers.view')
    ->name('hr.settings.centers.index');

Route::livewire('hr/centers/{center}', 'hr::settings.center')
    ->can('centers.view')
    ->whereNumber('center')
    ->name('hr.settings.centers.show');
