<?php

use App\Livewire\Recruitment\PublicSite\Apply;
use Illuminate\Support\Facades\Route;

/**
 * Unauthenticated recruitment routes owned by Team 2. Separate from
 * routes/recruitment.php, which is require()'d inside web.php's 'auth'
 * group — a public page there would redirect to login.
 *
 * TEMPORARY: this is Team 2's test form for exercising
 * RecruitmentApplicationSubmissionService. The real applicant form is Team
 * 1's `GET /careers/{token}` (careers.show, PublicCareerController) on
 * rms-main — the CV/document wizard. This one lives on its own URI so the two
 * never collide, and is not registered in production at all. Remove it once
 * Team 1's form is merged and adopted (see docs/recruitment/phase-2/).
 */
if (! app()->isProduction()) {
    Route::get('/careers-test/{token}', Apply::class)
        ->middleware('throttle:20,1')
        ->name('careers.test-apply');
}
