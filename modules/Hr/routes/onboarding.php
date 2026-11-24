<?php

use Illuminate\Support\Facades\Route;

/*
| Onboarding: the direct manager's session with a new volunteer (gated by
| OnboardingJourneys::canRunManagerOrientation, not a permission), the
| volunteer's own policies, and HR's list of the required ones. Loaded by
| HrServiceProvider inside web + auth + 2FA.
*/
Route::prefix('hr/onboarding')->name('hr.onboarding.')->group(function (): void {
    Route::livewire('manager-session/{employee}', 'hr::onboarding.manager-session')
        ->whereNumber('employee')
        ->name('manager-session');

    Route::livewire('my-first-days', 'hr::onboarding.my-first-days')
        ->name('my-first-days');

    Route::livewire('policies', 'hr::onboarding.policies')
        ->name('policies');

    Route::livewire('required-policies', 'hr::onboarding.required-policies')
        ->can('employees.update')
        ->name('required-policies');
});
