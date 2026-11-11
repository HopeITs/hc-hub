<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Hr\Support\Recruitment\Http\RecruitmentApplicantErasureController;
use Modules\Hr\Support\Recruitment\Http\RecruitmentApplicantExportController;
use Modules\Hr\Support\Recruitment\Http\RecruitmentApplicantFileController;

/*
| Applicants, their files, the candidate export and erasure, and the matching
| indicator rules.
|
| The list and the profile have no permission check at the door on purpose:
| named review participants open a profile without any applicant
| permission. The pages resolve every row through profileVisibleTo() and the
| policy, so a guessed id outside either scope is a 404.
*/

Route::prefix('hr/recruitment/applicants')->name('hr.recruitment.applicants.')->group(function (): void {
    Route::livewire('/', 'hr::recruitment.applicants.index')->name('index');
    Route::livewire('/{applicantId}', 'hr::recruitment.applicants.show')->whereNumber('applicantId')->name('show');

    // POST (CSRF-protected) and no permission check at the door: the policy
    // enforces the explicit export / erasure permission inside the controller,
    // so a refused attempt is audited instead of being stopped here.
    Route::post('/export', RecruitmentApplicantExportController::class)
        ->middleware('throttle:10,1')
        ->name('export');
    Route::post('/{applicant}/erasure', [RecruitmentApplicantErasureController::class, 'store'])
        ->whereNumber('applicant')
        ->middleware('throttle:10,1')
        ->name('erasure');
});

// The policy, not a permission check here, decides: a refused attempt still
// reaches the controller and lands in the download audit.
Route::get('hr/recruitment/applicant-files/{file}/download', [RecruitmentApplicantFileController::class, 'download'])
    ->whereNumber('file')
    ->middleware('throttle:60,1')
    ->name('hr.recruitment.applicant-files.download');

// HR's editor for the matching-indicator rules; the page checks
// recruitment-indicators.manage against the active role itself.
Route::livewire('hr/recruitment/indicator-rules', 'hr::recruitment.indicators.rules')->name('hr.recruitment.indicator-rules');
