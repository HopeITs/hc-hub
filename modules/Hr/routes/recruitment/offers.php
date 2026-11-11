<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Hr\Support\Recruitment\Http\RecruitmentJobOfferResponseEvidenceController;

/*
| Job offers and the hire. The list and a single offer have no permission
| check of their own on purpose: the manager who requested the position
| reads them without any recruitment permission. The pages resolve every
| offer through RecruitmentJobOffer::visibleTo() and the policy (a guessed id
| is a 404). Writing checks recruitment-applicants.manage-offers on the active
| role, the hire recruitment-applicants.convert-hires, and the policies.
*/

Route::prefix('hr/recruitment/offers')->name('hr.recruitment.offers.')->group(function (): void {
    Route::livewire('/', 'hr::recruitment.offers.index')->name('index');
    Route::livewire('/{offerId}', 'hr::recruitment.offers.show')->whereNumber('offerId')->name('show');
    Route::livewire('/{offerId}/edit', 'hr::recruitment.offers.form')->whereNumber('offerId')->name('edit');
    Route::livewire('/{offerId}/revise', 'hr::recruitment.offers.revise')->whereNumber('offerId')->name('revise');
    Route::livewire('/{offerId}/convert', 'hr::recruitment.offers.convert')->whereNumber('offerId')->name('convert');
});

Route::livewire('hr/recruitment/applications/{applicationId}/offer/create', 'hr::recruitment.offers.form')
    ->whereNumber('applicationId')
    ->name('hr.recruitment.offers.create');

Route::get('hr/recruitment/offer-response-evidence/{media}/download', RecruitmentJobOfferResponseEvidenceController::class)
    ->whereNumber('media')
    ->middleware('throttle:60,1')
    ->name('hr.recruitment.offer-response-evidence.download');
