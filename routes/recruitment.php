<?php

use App\Http\Controllers\RecruitmentApplicantFileController;
use App\Livewire\Recruitment\Applicants\Index as ApplicantIndex;
use App\Livewire\Recruitment\Applicants\Show as ApplicantShow;
use App\Livewire\Recruitment\Approvals\Pending;
use App\Livewire\Recruitment\Campaigns\Create as CampaignCreate;
use App\Livewire\Recruitment\Campaigns\Index as CampaignIndex;
use App\Livewire\Recruitment\Campaigns\Invites;
use App\Livewire\Recruitment\Campaigns\Show as CampaignShow;
use App\Livewire\Recruitment\Requests\Create;
use App\Livewire\Recruitment\Requests\Details;
use App\Livewire\Recruitment\Requests\DirectOpen;
use App\Livewire\Recruitment\Requests\Edit;
use App\Livewire\Recruitment\Requests\Index;
use Illuminate\Support\Facades\Route;

Route::prefix('recruitment/requests')->name('recruitment.requests.')
    ->middleware('active_permission:recruitment-requests.view')
    ->group(function () {
        Route::get('/', Index::class)->name('index');
        Route::get('/create', Create::class)->middleware('active_permission:recruitment-requests.manage')->name('create');
        // The shortcut is behind its own permission, never implied by manage.
        Route::get('/direct-open', DirectOpen::class)
            ->middleware('active_permission:recruitment-requests.direct-open')->name('direct-open');
        Route::get('/{requestId}/edit', Edit::class)->whereNumber('requestId')->middleware('active_permission:recruitment-requests.manage')->name('edit');
        Route::get('/{requestId}', Details::class)->whereNumber('requestId')->name('show');
    });

Route::get('recruitment/approvals', Pending::class)
    ->middleware(['active_permission:recruitment-requests.approve'])
    ->name('recruitment.approvals.pending');

Route::prefix('recruitment/campaigns')->name('recruitment.campaigns.')
    ->middleware('active_permission:recruitment-campaigns.view')
    ->group(function () {
        Route::get('/', CampaignIndex::class)->name('index');
        Route::get('/create', CampaignCreate::class)
            ->middleware('active_permission:recruitment-campaigns.manage')->name('create');
        Route::get('/{campaignId}', CampaignShow::class)->whereNumber('campaignId')->name('show');
    });

// The permission is the outer wall; each applicant is re-resolved through the
// recruitment scope inside the component, so a guessed id is a 404.
Route::prefix('recruitment/applicants')->name('recruitment.applicants.')
    ->middleware('active_permission:recruitment-applicants.view')
    ->group(function () {
        Route::get('/', ApplicantIndex::class)->name('index');
        Route::get('/{applicantId}', ApplicantShow::class)->whereNumber('applicantId')->name('show');
    });

// No permission middleware on purpose: the policy enforces it, so a refused
// attempt still reaches the controller and lands in the download audit.
Route::get('recruitment/applicant-files/{file}/download', [RecruitmentApplicantFileController::class, 'download'])
    ->whereNumber('file')
    ->middleware('throttle:60,1')
    ->name('recruitment.applicant-files.download');

// Team 2 (Phase 2 Task 1): stand-alone page for a campaign's invite list.
// The same InviteTable is also embedded in Team 1's publishing page.
Route::get('recruitment/campaigns/{campaignId}/invites', Invites::class)
    ->whereNumber('campaignId')
    ->middleware('active_permission:recruitment-applicants.manage')
    ->name('recruitment.campaigns.invites');
