<?php

use App\Models\Activity;
use App\Models\UserNotification;
use App\Notifications\Alerts\NotificationSettings;
use App\Notifications\Alerts\WorkingCalendar;
use Carbon\CarbonImmutable;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Modules\Core\Models\User;
use Spatie\Permission\PermissionRegistrar;

use function Livewire\invade;

test('only administrators see the organization rules section', function (string $role, bool $manages) {
    $page = $this->actingAs(userWithRole($role))->get(route('notification-settings.edit'))->assertOk();

    $page->assertDontSee('data-settings-tabs', false)->assertSee('data-module-mutes', false);

    $manages
        ? $page->assertSee('id="organization-rules"', false)
        : $page->assertDontSee('id="organization-rules"', false);
})->with([
    ['Super Admin', true],
    ['General Manager', false],
    ['Managers', false],
]);

test('the rules form is forbidden to people without the permission', function (string $role) {
    Livewire::actingAs(userWithRole($role))->test('notifications.rules')->assertForbidden();
})->with(['General Manager', 'Managers']);

test('a general manager who is given the permission directly gets the organization rules section', function () {
    $generalManager = userWithRole('General Manager');

    $this->actingAs($generalManager)->get(route('notification-settings.edit'))->assertDontSee('id="organization-rules"', false);

    $generalManager->givePermissionTo('notification-settings.manage');

    $this->actingAs($generalManager->fresh())->get(route('notification-settings.edit'))->assertSee('id="organization-rules"', false);
});

test('the rules load only when their section is opened, and the old rules address redirects to it', function () {
    $admin = userWithRole('Super Admin');

    $this->actingAs($admin)->get(route('notification-settings.edit'))->assertOk()->assertSee('id="organization-rules"', false)->assertDontSee('data-notification-rules', false);
    $this->actingAs($admin)->get('/admin/notification-settings')->assertRedirect('/notifications-settings#organization-rules');
});

test('an administrator changes thresholds and working days and they take effect', function () {
    Livewire::actingAs(userWithRole('Super Admin'))->test('notifications.rules')
        ->assertSet('workingDays', [1, 2, 3, 4, 5])
        ->assertSet('dueSoonDays', '7, 3, 1')
        ->set('workingDays', [7, 1, 2, 3, 4])
        ->set('dueSoonDays', '10, 5, 2')
        ->set('overdueRepeat', 2)
        ->set('monthlyReportDue', 7)
        ->set('dailyBriefTime', '07:00')
        ->call('save')
        ->assertHasNoErrors();

    $settings = app(NotificationSettings::class);

    expect($settings->get('due_soon_days'))->toBe([10, 5, 2])
        ->and($settings->get('overdue_repeat_days'))->toBe(2)
        ->and($settings->get('monthly_report_due_working_days'))->toBe(7)
        ->and($settings->get('daily_brief_time'))->toBe('07:00')
        ->and(app(WorkingCalendar::class)->isWorkingDay(CarbonImmutable::parse('2026-09-27')))->toBeTrue();
});

test('settings are validated', function (string $property, mixed $value) {
    Livewire::actingAs(userWithRole('Super Admin'))->test('notifications.rules')
        ->set($property, $value)
        ->call('save')
        ->assertHasErrors($property);
})->with([
    'no working day' => ['workingDays', []],
    'a bad weekday' => ['workingDays', [8]],
    'a bad stage list' => ['dueSoonDays', '7, soon'],
    'a zero repeat' => ['overdueRepeat', 0],
    'a monthly report deadline over 20' => ['monthlyReportDue', 21],
    'top before manager' => ['escalateTopAfter', 3],
    'a pace over 100' => ['offTrackPace', 150],
    'a bad time' => ['weeklyDigestTime', '8am'],
]);

/**
 * The rules form mounted by someone who then loses the permission, so only the action's own check stands in the way.
 */
function rulesFormWithRevokedPermission(): Testable
{
    $user = userWithRole('General Manager');
    $user->givePermissionTo('notification-settings.manage');

    $component = Livewire::actingAs($user)->test('notifications.rules');

    $user->revokePermissionTo('notification-settings.manage');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $component;
}

test('a non-administrator cannot save by calling the action', function () {
    rulesFormWithRevokedPermission()->call('save')->assertForbidden();
});

test('in arabic every working-day checkbox is labelled with its own weekday', function () {
    app()->setLocale('ar');

    $html = Livewire::actingAs(userWithRole('Super Admin'))->test('notifications.rules')->html();
    preg_match_all('/data-weekday="(\d)".*?value="\1".*?<span class="rules-day-name">\s*<span[^>]*>([^<]+?)<\/span>/su', $html, $matches, PREG_SET_ORDER);

    expect(collect($matches)->mapWithKeys(fn (array $match): array => [(int) $match[1] => trim($match[2])])->all())
        ->toBe([1 => 'الاثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت', 7 => 'الأحد']);
});

test('the escalation catch-up window is saved and validated', function () {
    Livewire::actingAs(userWithRole('Super Admin'))->test('notifications.rules')
        ->assertSet('escalationCatchUp', 3)
        ->set('escalationCatchUp', 5)
        ->call('save')
        ->assertHasNoErrors();

    expect(app(NotificationSettings::class)->get('escalation_catch_up_days'))->toBe(5);

    Livewire::actingAs(userWithRole('Super Admin'))->test('notifications.rules')
        ->set('escalationCatchUp', 0)
        ->call('save')
        ->assertHasErrors('escalationCatchUp');
});

test('saving hands the page back the stored, normalised rules and tells it to take a fresh snapshot', function () {
    Livewire::actingAs(userWithRole('Super Admin'))->test('notifications.rules')
        ->set('workingDays', ['5', '1', '3', '1'])
        ->set('dueSoonDays', '3,10 , 3')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('workingDays', [1, 3, 5])
        ->assertSet('dueSoonDays', '10, 3')
        ->assertDispatched('notification-rules-saved');

    expect(app(NotificationSettings::class)->get('working_days'))->toBe([1, 3, 5])
        ->and(app(NotificationSettings::class)->get('due_soon_days'))->toBe([10, 3]);
});

test('a failed save neither stores the rules nor tells the page to take a snapshot', function () {
    Livewire::actingAs(userWithRole('Super Admin'))->test('notifications.rules')
        ->set('overdueRepeat', 0)
        ->call('save')
        ->assertNotDispatched('notification-rules-saved');

    expect(app(NotificationSettings::class)->get('overdue_repeat_days'))->toBe(3);
});

test('the reset-to-defaults values are the shipped defaults, not the stored rules', function () {
    app(NotificationSettings::class)->save(['working_days' => [7, 1, 2, 3, 4], 'due_soon_days' => [5], 'stalled_proposal_days' => 45]);

    $component = Livewire::actingAs(userWithRole('Super Admin'))->test('notifications.rules');

    expect($component->get('stalledProposal'))->toBe(45)
        ->and(invade($component->instance())->clientConfig()['defaults'])->toBe([
            'workingDays' => [1, 2, 3, 4, 5],
            'dueSoonDays' => '7, 3, 1',
            'overdueRepeat' => 3,
            'departmentTaskDue' => 5,
            'monthlyReportDue' => 5,
            'escalateManagerAfter' => 5,
            'escalateTopAfter' => 10,
            'escalationCatchUp' => 3,
            'stalledTaskInProgress' => 7,
            'stalledTaskUnderReview' => 3,
            'stalledReportDraft' => 7,
            'stalledReportReviewed' => 5,
            'stalledProposal' => 30,
            'extensionLead' => 60,
            'offTrackPace' => 80,
            'dailyBriefTime' => '07:30',
            'weeklyDigestTime' => '08:00',
        ]);
});

test('discarding the draft clears the errors of a failed save', function () {
    Livewire::actingAs(userWithRole('Super Admin'))->test('notifications.rules')
        ->set('overdueRepeat', 0)
        ->call('save')
        ->assertHasErrors('overdueRepeat')
        ->set('overdueRepeat', 3)
        ->call('forgetErrors')
        ->assertHasNoErrors();
});

test('in arabic the page script gets one plural form per arabic range', function () {
    app()->setLocale('ar');

    $plurals = invade(Livewire::actingAs(userWithRole('Super Admin'))->test('notifications.rules')->instance())->clientConfig()['plurals'];

    expect($plurals['workingDays'])->toBe([1 => 'يوم عمل', 2 => 'يوما عمل', 3 => 'أيام عمل', 11 => 'يوم عمل'])
        ->and($plurals['unsaved'][11])->toBe(':count تغييرًا غير محفوظ');
});

test('the page renders every rule section with its controls', function () {
    Livewire::actingAs(userWithRole('Super Admin'))->test('notifications.rules')
        ->assertSeeHtml('id="rules-week"')
        ->assertSeeHtml('id="rules-deadlines"')
        ->assertSeeHtml('id="rules-stalled"')
        ->assertSeeHtml('id="rules-digests"')
        ->assertSeeHtml('id="rule-escalateTopAfter"')
        ->assertSeeHtml('id="rule-stalledProposal"')
        ->assertSeeHtml('id="rule-weeklyDigestTime"')
        ->assertSee(__('notifications.admin.stalled_report_reviewed'));
});

/**
 * A General Manager granted notification-settings.manage directly, so the
 * default-reviewer tests never rely on Super Admin alone (D-20a).
 */
function reviewerSettingsAdmin(): User
{
    $user = userWithRole('General Manager');
    $user->givePermissionTo('notification-settings.manage');

    return $user->fresh();
}

test('the default proposal reviewers section stores a position for each slot and shows it again', function () {
    $finance = insertHrPosition(insertHrDepartment('Finance'), 'Finance Manager');
    $ceo = insertHrPosition(insertHrDepartment('Board'), 'CEO');

    Livewire::actingAs(reviewerSettingsAdmin())->test('notifications.rules')
        ->assertSeeHtml('id="rules-default-reviewers"')
        ->set('reviewerPositions.finance_manager', (string) $finance)
        ->set('reviewerPositions.ceo', (string) $ceo)
        ->call('saveDefaultReviewers')
        ->assertHasNoErrors();

    expect(app(NotificationSettings::class)->get('proposal_default_reviewers'))
        ->toEqual(['meal_manager' => null, 'finance_manager' => $finance, 'fundraising_manager' => null, 'ceo' => $ceo]);

    Livewire::actingAs(reviewerSettingsAdmin())->test('notifications.rules')
        ->assertSet('reviewerPositions.finance_manager', (string) $finance)
        ->assertSet('reviewerPositions.meal_manager', '');
});

test('a default reviewer slot must be an HR position', function (string $value) {
    Livewire::actingAs(reviewerSettingsAdmin())->test('notifications.rules')
        ->set('reviewerPositions.ceo', $value)
        ->call('saveDefaultReviewers')
        ->assertHasErrors(['reviewerPositions.ceo']);

    expect(app(NotificationSettings::class)->get('proposal_default_reviewers')['ceo'])->toBeNull();
})->with(['not a number' => ['ceo'], 'unknown in HR' => ['999999']]);

test('without notification-settings.manage nobody saves the default reviewers', function () {
    rulesFormWithRevokedPermission()->call('saveDefaultReviewers')->assertForbidden();
});

test('moving the MEAL Manager slot to another position is logged and tells both holders', function () {
    $previous = holdMealManagerSlot();
    $previousUser = userWithRole('MEAL', $previous);
    $nextHolder = insertHrEmployee(firstNameEn: 'Nadia', lastNameEn: 'Next');
    $nextPosition = insertHrPosition(insertHrDepartment('MEAL'), 'MEAL Manager');
    insertHrContract($nextHolder, $nextPosition);
    $nextUser = userWithRole('MEAL', $nextHolder);

    Livewire::actingAs(reviewerSettingsAdmin())->test('notifications.rules')
        ->set('reviewerPositions.meal_manager', (string) $nextPosition)
        ->call('saveDefaultReviewers')
        ->assertHasNoErrors();

    $activity = Activity::query()->where('event', 'notification_settings.meal_manager_changed')->sole();
    $key = 'notifications.events.notification_settings.meal_manager_changed';

    expect($activity->properties['employee_id'])->toBe($nextHolder)
        ->and($activity->properties['previous_employee_id'])->toBe($previous)
        ->and(UserNotification::query()->forUser($previousUser)->get()->pluck('data.key')->all())->toBe([$key])
        ->and(UserNotification::query()->forUser($nextUser)->get()->pluck('data.key')->all())->toBe([$key]);
});

test('saving the default reviewers without moving the MEAL Manager logs nothing', function () {
    holdMealManagerSlot();

    Livewire::actingAs(reviewerSettingsAdmin())->test('notifications.rules')
        ->set('reviewerPositions.ceo', (string) insertHrPosition(insertHrDepartment('Board'), 'CEO'))
        ->call('saveDefaultReviewers');

    expect(Activity::query()->where('event', 'notification_settings.meal_manager_changed')->exists())->toBeFalse();
});

test('the page script is handed its real config, not an uncompiled directive', function () {
    $html = Livewire::actingAs(userWithRole('Super Admin'))->test('notifications.rules')->html();

    expect($html)->toContain('x-data="notificationRules(')
        ->not->toContain('@js(')
        ->not->toContain('clientConfig()');
});
