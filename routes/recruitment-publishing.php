<?php

use App\Livewire\Recruitment\JobPosts\Editor as JobPostEditor;
use Illuminate\Support\Facades\Route;

Route::prefix('recruitment/campaigns')
    ->name('recruitment.campaigns.')
    ->group(function () {
        Route::get('/{campaign}/job-post', JobPostEditor::class)
            ->middleware('active_permission:recruitment-job-posts.manage')
            ->name('job-post.edit');
    });
