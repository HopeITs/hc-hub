<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Hr\Support\News\ShowNewsCover;

/*
 * HR communications: news and events, the work inbox, in-app feedback, the
 * old activity log, statistics and the assistant.
 */

Route::livewire('/hr/news', 'hr::news.index')->name('hr.news.index');
Route::livewire('/hr/news/create', 'hr::news.form')->name('hr.news.create');
Route::livewire('/hr/news/{news}', 'hr::news.show')->whereNumber('news')->name('hr.news.show');
Route::livewire('/hr/news/{news}/preview', 'hr::news.show')->whereNumber('news')->name('hr.news.preview');
Route::livewire('/hr/news/{news}/edit', 'hr::news.form')->whereNumber('news')->name('hr.news.edit');
Route::get('/hr/news/{news}/cover', ShowNewsCover::class)->whereNumber('news')->name('hr.news.cover');

Route::livewire('/hr/inbox', 'hr::inbox.index')->name('hr.inbox');
Route::livewire('/hr/feedback', 'hr::feedback.index')->name('hr.feedback.index');
Route::livewire('/hr/activity', 'hr::activity.index')->name('hr.activity.index');
Route::livewire('/hr/statistics', 'hr::desk.statistics')->name('hr.statistics.index');
Route::livewire('/hr/assistant', 'hr::assistant.index')->name('hr.assistant');
