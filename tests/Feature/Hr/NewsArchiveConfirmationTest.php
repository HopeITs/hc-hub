<?php

use Livewire\Livewire;
use Modules\Hr\Enums\News\NewsStatus;
use Modules\Hr\Models\News;

test('archiving news asks first and the item stays published until it is confirmed', function () {
    $admin = userWithRole(config('access-control.administrator_roles')[0]);
    $news = (new News)->forceFill([
        'type' => 'news', 'status' => NewsStatus::Published, 'title_ar' => 'خبر', 'title_en' => 'A story',
        'description_ar' => 'نص', 'description_en' => 'Body', 'published_at' => now()->subDay(), 'created_by' => $admin->id,
    ]);
    $news->save();

    $page = Livewire::actingAs($admin)->test('hr::news.index')
        ->assertSeeHtml('wire:click="confirmRemoveArchive('.$news->id.')"')
        ->call('confirmRemoveArchive', $news->id)
        ->assertSet('showRemoveArchiveModal', true);

    expect($news->fresh()->status)->toBe(NewsStatus::Published);

    $page->call('cancelRemoveArchive')->assertSet('showRemoveArchiveModal', false);
    expect($news->fresh()->status)->toBe(NewsStatus::Published);

    $page->call('confirmRemoveArchive', $news->id)->call('removeConfirmedArchive');
    expect($news->fresh()->status)->toBe(NewsStatus::Archived);
});

test('every confirmation text exists in English and Arabic', function (string $key) {
    foreach (['en', 'ar'] as $locale) {
        app()->setLocale($locale);
        expect(__($key))->not->toBe($key);
    }
})->with([
    'projects.unlink_confirm_title', 'projects.unlink_confirm_message', 'projects.unlink_confirm_button',
    'annual_plans.up.remove_file_title', 'annual_plans.up.remove_file_message',
    'annual_plans.wf.remove_follow_up_title', 'annual_plans.wf.remove_follow_up_message',
    'risks.detach_confirm_title', 'risks.detach_confirm_message', 'risks.detach_confirm_button',
    'tasks.remove_assignee_confirm_title', 'tasks.remove_assignee_confirm_message',
    'strategic_plans.remove_indicator_title', 'strategic_plans.remove_indicator_message',
    'hr_news.list.archive_title', 'hr_news.list.archive_body',
    'hr_desk.cards.notes.remove_title', 'hr_desk.cards.notes.remove_body',
]);
