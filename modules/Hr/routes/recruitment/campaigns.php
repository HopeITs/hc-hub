<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
| Hiring campaigns and their publishing: the job post, the application form,
| public links and invitations. Each page checks its permission against the
| active role and asks the campaign policy.
*/

Route::prefix('hr/recruitment/campaigns')->name('hr.recruitment.campaigns.')->group(function (): void {
    Route::livewire('/', 'hr::recruitment.campaigns.index')->name('index');
    Route::livewire('/create/{vacancyId?}', 'hr::recruitment.campaigns.create')->whereNumber('vacancyId')->name('create');
    Route::livewire('/{campaignId}', 'hr::recruitment.campaigns.show')->whereNumber('campaignId')->name('show');
    Route::livewire('/{campaignId}/invites', 'hr::recruitment.campaigns.invites')->whereNumber('campaignId')->name('invites');
    Route::livewire('/{campaignId}/job-post', 'hr::recruitment.campaigns.job-post')->whereNumber('campaignId')->name('job-post.edit');
    Route::livewire('/{campaignId}/application-form', 'hr::recruitment.campaigns.application-form')->whereNumber('campaignId')->name('application-form.edit');
    Route::livewire('/{campaignId}/links', 'hr::recruitment.campaigns.links')->whereNumber('campaignId')->name('links');
});
