<?php

use App\Livewire\Recruitment\PublicSite\Apply;
use Illuminate\Support\Facades\Route;

/**
 * Unauthenticated recruitment routes only. This file is intentionally
 * separate from routes/recruitment.php, which is require()'d inside web.php's
 * 'auth' middleware group — merging this route into it would put the public
 * application page behind a login redirect. Required from web.php outside
 * both the locale group and the 'auth' group, the same way the digital-id
 * card routes are.
 */

// Public job application page, resolved from an opaque bearer token rather
// than a session. Throttled against scraping/spam per Phase 1 Task 15's
// anti-abuse requirement — a CAPTCHA or a stronger bot check is a follow-up,
// not done here.
Route::get('/careers/{token}', Apply::class)
    ->middleware('throttle:20,1')
    ->name('careers.apply');
