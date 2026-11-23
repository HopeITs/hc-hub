<?php

use App\Models\Role;
use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Hr\Models\RecruitmentTag;
use Modules\Hr\Support\Recruitment\Access;

/*
| Roadmap 0.6: every filter on the applicants list says what it filters when
| it is empty, and its button carries an accessible name (label + choice).
*/

test('every applicants filter names what it filters when empty', function (string $locale) {
    app()->setLocale($locale);
    Role::findOrCreate('super-admin', 'web');
    $user = User::factory()->create();
    $user->syncRoles(['super-admin']);
    $user->forceFill(['active_role_id' => Role::findByName('super-admin', 'web')->id])->save();
    Access::flush();
    RecruitmentTag::query()->create(['name' => 'Shortlist', 'scope_key' => 'all', 'created_by_user_id' => $user->id]);

    $page = Livewire::actingAs($user)->test('hr::recruitment.applicants.index');

    $filters = [
        'all_sources' => 'applicant_ui.source',
        'all_cv' => 'applicant_ui.cv',
        'all_previous' => 'applicant_ui.previous_applicant',
        'all_tags' => 'applicant_ui.tags.title',
        'all_flag_types' => 'applicant_ui.flags.type',
        'all_flag_categories' => 'applicant_ui.flags.category',
        'all_endorsements' => 'applicant_ui.endorsement.groups_label',
        'all_evaluations' => 'applicant_ui.dashboard.evaluation_filter',
    ];

    foreach ($filters as $key => $labelKey) {
        $all = __('hr_recruitment.applicant_ui.filters.'.$key);
        $label = __('hr_recruitment.'.$labelKey);

        expect($all)->not->toBe('hr_recruitment.applicant_ui.filters.'.$key);
        $page->assertSeeHtml('<span class="truncate">'.e($all).'</span>')
            ->assertSeeHtml('aria-label="'.e(__('common.filter_choice', ['label' => $label, 'choice' => $all])).'"');
    }
})->with(['en', 'ar']);
