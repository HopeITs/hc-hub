<?php

use App\Livewire\Recruitment\ApplicationForms\Editor as ApplicationFormEditor;
use App\Livewire\Recruitment\JobPosts\Editor as JobPostEditor;
use Illuminate\Support\Facades\Route;

Route::prefix('recruitment/campaigns')
    ->name('recruitment.campaigns.')
    ->group(function () {
        Route::get('/{campaign}/job-post', JobPostEditor::class)
            ->middleware('active_permission:recruitment-job-posts.manage')
            ->name('job-post.edit');

        Route::get('/{campaign}/application-form', ApplicationFormEditor::class)
            ->middleware('active_permission:recruitment-forms.manage')
            ->name('application-form.edit');
    });
