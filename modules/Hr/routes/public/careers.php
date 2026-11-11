<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Hr\Support\Recruitment\Http\PublicCareerController;
use Modules\Hr\Support\Recruitment\Http\RecruitmentAssetController;

/*
| The public careers site. Links are already shared outside the system as
| /careers/{token}, so the address stays; the names move under hr.careers.
| The rate limiters are defined by RecruitmentBootstrap.
*/

Route::get('/careers/{token}', [PublicCareerController::class, 'show'])
    ->middleware('throttle:careers-view')
    ->name('hr.careers.show');
Route::get('/careers/{token}/submitted', [PublicCareerController::class, 'submitted'])
    ->middleware('throttle:careers-view')
    ->name('hr.careers.submitted');
Route::get('/careers/{token}/sign-in', [PublicCareerController::class, 'signIn'])
    ->middleware('throttle:careers-view')
    ->name('hr.careers.sign-in');
Route::post('/careers/{token}/invitation', [PublicCareerController::class, 'checkInvitation'])
    ->middleware('throttle:careers-invitation')
    ->name('hr.careers.invitation');
Route::post('/careers/{token}/applications', [PublicCareerController::class, 'store'])
    ->middleware('throttle:careers-submit')
    ->name('hr.careers.submit');

// The fixed Hope Center artwork and fonts (job-post graphics, careers banner).
Route::get('/hr/recruitment/assets/{file}', RecruitmentAssetController::class)
    ->where('file', '[a-z0-9\-]+\.(svg|ttf)')
    ->name('hr.recruitment.assets');
