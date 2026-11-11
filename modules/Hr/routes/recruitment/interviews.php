<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Hr\Support\Recruitment\Http\RecruitmentInterviewAttachmentController;

/*
| Interviews. The list and a single interview have no permission guard on
| purpose: participants see the interviews they are on without holding any
| applicant permission, and the pages resolve every interview through
| RecruitmentInterview::visibleTo() and the policy (out of reach is a 404).
| Scheduling and editing check recruitment-applicants.schedule-interviews on
| the active role, in the page.
*/

Route::prefix('hr/recruitment/interviews')->name('hr.recruitment.interviews.')->group(function (): void {
    Route::livewire('/', 'hr::recruitment.interviews.index')->name('index');
    Route::livewire('/{interviewId}', 'hr::recruitment.interviews.show')->whereNumber('interviewId')->name('show');
    Route::livewire('/{interviewId}/edit', 'hr::recruitment.interviews.form')->whereNumber('interviewId')->name('edit');
});

Route::livewire('hr/recruitment/applications/{applicationId}/interviews/create', 'hr::recruitment.interviews.form')
    ->whereNumber('applicationId')
    ->name('hr.recruitment.interviews.create');

Route::get('hr/recruitment/interview-attachments/{media}/download', RecruitmentInterviewAttachmentController::class)
    ->whereNumber('media')
    ->middleware('throttle:60,1')
    ->name('hr.recruitment.interview-attachments.download');
