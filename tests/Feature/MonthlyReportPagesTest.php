<?php

use App\Enums\MonthlyReportStatus;
use App\Models\Activity;
use App\Models\MealCoverageAssignment;
use App\Models\MonthlyReport;
use App\Models\MonthlyReportLine;
use App\Models\MonthlyReportReopenRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/*
| The Monthly Reports list (T17B slice 1; contracts §13, §13.1 items 2, 6
| and 9; D-08, D-12a, D-29). "My reports" lists every generated month of the
| user's projects with its state, due date and overdue marker; "To validate"
| is MonthlyReport::validatableBy() limited to submitted reports.
*/

beforeEach(function () {
    $this->aleppo = insertHrCity('Aleppo');
    $this->ownerEmployee = insertHrEmployee(firstNameEn: 'Omar', lastNameEn: 'Owner');
    $this->owner = userWithRole('Public Relations', $this->ownerEmployee);
});

function mrpProject(int $cityId, ?int $ownerEmployee = null, string $start = '2026-07-01', string $end = '2027-01-31', string $name = 'Hope Center Aleppo'): Project
{
    return Activity::withoutRecording(fn (): Project => Project::factory()->active()->create([
        'name' => $name,
        'city_id' => $cityId,
        'center_id' => null,
        'user_id' => $ownerEmployee ?? test()->ownerEmployee,
        'start_date' => $start,
        'end_date' => $end,
    ]));
}

/**
 * A month of the project in the given status, with one line so it is a
 * required month (§13.1 item 2).
 *
 * @param  array<string, mixed>  $attributes
 */
function mrpMonth(Project $project, string $period, MonthlyReportStatus $status = MonthlyReportStatus::Draft, array $attributes = []): MonthlyReport
{
    return Activity::withoutRecording(function () use ($project, $period, $status, $attributes): MonthlyReport {
        $factory = match ($status) {
            MonthlyReportStatus::Draft => MonthlyReport::factory(),
            MonthlyReportStatus::Submitted => MonthlyReport::factory()->submitted(),
            MonthlyReportStatus::Validated => MonthlyReport::factory()->validated(),
        };
        $report = $factory->create(['project_id' => $project->id, 'period' => $period, ...$attributes]);
        MonthlyReportLine::factory()->create(['monthly_report_id' => $report->id]);

        return $report;
    });
}

test('guests are redirected and every reports.view holder opens the list', function () {
    $this->get(route('monthly-reports.index'))->assertRedirect(route('login'));

    $this->actingAs(userWithRole('Team Member'))->get(route('monthly-reports.index'))->assertOk();
    $this->actingAs(User::factory()->create())->get(route('monthly-reports.index'))->assertForbidden();
});

test('a center manager sees every generated month with its state, and only a started late draft is overdue', function () {
    $this->travelTo('2026-11-20 10:00:00');
    $project = mrpProject($this->aleppo);
    mrpMonth($project, '2026-07-01', MonthlyReportStatus::Validated);
    $august = mrpMonth($project, '2026-08-01', MonthlyReportStatus::Validated);
    Activity::withoutRecording(fn () => MonthlyReportReopenRequest::factory()->create(['monthly_report_id' => $august->id, 'requested_by' => $this->owner->id]));
    mrpMonth($project, '2026-09-01', MonthlyReportStatus::Submitted);
    mrpMonth($project, '2026-10-01', attributes: ['returned_at' => '2026-11-05 09:00:00', 'returned_by' => User::factory(), 'return_reason' => 'Figures do not match the attendance sheets.']);
    $november = mrpMonth($project, '2026-11-01');
    mrpMonth($project, '2026-12-01');

    $component = Livewire::actingAs($this->owner)->test('pages::monthly-reports.index')
        ->assertSet('tab', 'mine')
        ->assertSeeHtml('data-month="2026-07" data-month-state="validated"')
        ->assertSeeHtml('data-month="2026-08" data-month-state="reopen_pending"')
        ->assertSeeHtml('data-month="2026-09" data-month-state="submitted"')
        ->assertSeeHtml('data-month="2026-10" data-month-state="returned"')
        ->assertSeeHtml('data-month="2026-11" data-month-state="draft"')
        ->assertSeeHtml('data-month="2026-12" data-month-state="upcoming"')
        ->assertSeeHtml('data-overdue="2026-10"')
        ->assertDontSeeHtml('data-overdue="2026-11"')
        ->assertDontSeeHtml('data-overdue="2026-12"')
        ->assertSeeHtml('data-fill="'.$november->id.'"')
        ->assertSeeText(__('monthly_reports.states.reopen_pending'))
        ->assertSeeText(__('monthly_reports.states.upcoming'));

    $component->call('fillMonth', $november->id)->assertRedirect(route('monthly-reports.show', $november));
});

test('an upcoming month has no Fill button, only its view link', function () {
    $this->travelTo('2026-11-20 10:00:00');
    $december = mrpMonth(mrpProject($this->aleppo), '2026-12-01');

    Livewire::actingAs($this->owner)->test('pages::monthly-reports.index')
        ->assertDontSeeHtml('data-fill="'.$december->id.'"')
        ->assertSeeHtml(route('monthly-reports.show', $december));
});

test('a month blocked by an unsubmitted earlier month can still be filled and shows why it cannot be submitted', function () {
    $this->travelTo('2026-12-01 10:00:00');
    $project = mrpProject($this->aleppo, start: '2026-10-01', end: '2026-12-31');
    mrpMonth($project, '2026-10-01');
    $november = mrpMonth($project, '2026-11-01');

    Livewire::actingAs($this->owner)->test('pages::monthly-reports.index')
        ->assertSeeText(__('monthly_reports.submit_previous_first', ['month' => 'October 2026']))
        ->assertSeeHtml('data-fill="'.$november->id.'"')
        ->call('fillMonth', $november->id)
        ->assertRedirect(route('monthly-reports.show', $november));
});

test('a user with no project of their own is told why "My reports" is empty', function () {
    Livewire::actingAs(userWithRole('Public Relations', insertHrEmployee()))->test('pages::monthly-reports.index')
        ->assertSeeText(__('monthly_reports.empty_mine'));
});

test('MEAL lands on its queue and sees only the submitted reports of the projects it covers', function () {
    $officerEmployee = insertHrEmployee(firstNameEn: 'Nour', lastNameEn: 'Officer');
    Activity::withoutRecording(fn () => MealCoverageAssignment::factory()->forCity($this->aleppo)->create(['employee_id' => $officerEmployee]));
    $officer = userWithRole('MEAL', $officerEmployee);
    $covered = mrpProject($this->aleppo);
    $homsEmployee = insertHrEmployee(firstNameEn: 'Huda', lastNameEn: 'Homs');
    $homs = insertHrCity('Homs');
    Activity::withoutRecording(fn () => MealCoverageAssignment::factory()->forCity($homs)->create(['employee_id' => $homsEmployee]));
    $elsewhere = mrpProject($homs, name: 'Homs Center');
    $submitted = mrpMonth($covered, '2026-09-01', MonthlyReportStatus::Submitted);
    $draft = mrpMonth($covered, '2026-10-01');
    $foreign = mrpMonth($elsewhere, '2026-09-01', MonthlyReportStatus::Submitted);

    Livewire::actingAs($officer)->test('pages::monthly-reports.index')
        ->assertSet('tab', 'validate')
        ->assertSeeHtml('data-validate-row="'.$submitted->id.'"')
        ->assertDontSeeHtml('data-validate-row="'.$draft->id.'"')
        ->assertDontSeeHtml('data-validate-row="'.$foreign->id.'"');
});

test('a project officer from another governorate sees the project while the governorate officer does not', function () {
    $governorateEmployee = insertHrEmployee(firstNameEn: 'Nour', lastNameEn: 'Officer');
    Activity::withoutRecording(fn () => MealCoverageAssignment::factory()->forCity($this->aleppo)->create(['employee_id' => $governorateEmployee]));
    $idlibEmployee = insertHrEmployee(firstNameEn: 'Ziad', lastNameEn: 'Idlib');
    $project = mrpProject($this->aleppo);
    Activity::withoutRecording(fn () => MealCoverageAssignment::factory()->forCity(insertHrCity('Idlib'))->create(['employee_id' => $idlibEmployee]));
    Activity::withoutRecording(fn () => MealCoverageAssignment::factory()->forProject($project->id)->create(['employee_id' => $idlibEmployee]));
    $report = mrpMonth($project, '2026-09-01', MonthlyReportStatus::Submitted);

    Livewire::actingAs(userWithRole('MEAL', $idlibEmployee))->test('pages::monthly-reports.index')
        ->assertSeeHtml('data-validate-row="'.$report->id.'"');

    Livewire::actingAs(userWithRole('MEAL', $governorateEmployee))->test('pages::monthly-reports.index')
        ->assertDontSeeHtml('data-validate-row="'.$report->id.'"')
        ->assertSeeText(__('monthly_reports.empty_validate'));
});

test('with nobody responsible for the project any MEAL user sees it to validate', function () {
    $report = mrpMonth(mrpProject($this->aleppo), '2026-09-01', MonthlyReportStatus::Submitted);

    Livewire::actingAs(userWithRole('MEAL', insertHrEmployee(firstNameEn: 'Huda', lastNameEn: 'Meal')))
        ->test('pages::monthly-reports.index')
        ->assertSeeHtml('data-validate-row="'.$report->id.'"');
});

test('the queue filters by city, project and month', function () {
    $project = mrpProject($this->aleppo);
    $other = mrpProject($homs = insertHrCity('Homs'), name: 'Homs Center');
    $september = mrpMonth($project, '2026-09-01', MonthlyReportStatus::Submitted);
    $october = mrpMonth($project, '2026-10-01', MonthlyReportStatus::Submitted);
    $homsReport = mrpMonth($other, '2026-09-01', MonthlyReportStatus::Submitted);
    $meal = userWithRole('MEAL', insertHrEmployee(firstNameEn: 'Huda', lastNameEn: 'Meal'));

    Livewire::actingAs($meal)->test('pages::monthly-reports.index')
        ->set('city', (string) $homs)
        ->assertSeeHtml('data-validate-row="'.$homsReport->id.'"')
        ->assertDontSeeHtml('data-validate-row="'.$september->id.'"')
        ->set('city', '')
        ->set('project', (string) $project->id)
        ->set('month', '2026-10')
        ->assertSeeHtml('data-validate-row="'.$october->id.'"')
        ->assertDontSeeHtml('data-validate-row="'.$september->id.'"')
        ->assertDontSeeHtml('data-validate-row="'.$homsReport->id.'"');
});

test('a Team Member sees the months of a project they own without Fill and is refused the call', function () {
    $this->travelTo('2026-11-20 10:00:00');
    $november = mrpMonth(mrpProject($this->aleppo), '2026-11-01');
    $viewer = userWithRole('Team Member', $this->ownerEmployee);

    Livewire::actingAs($viewer)->test('pages::monthly-reports.index')
        ->assertSeeHtml('data-month="2026-11" data-month-state="draft"')
        ->assertDontSeeHtml('data-fill="'.$november->id.'"')
        ->call('fillMonth', $november->id)
        ->assertForbidden();

    expect($november->fresh()->prepared_by)->toBeNull();
});

/*
| Page review, 5 Nov 2026: what a first-time MEAL Manager and preparer
| tripped over on this list.
*/

test('a month kept while the status changes still names itself on its filter', function () {
    $project = mrpProject($this->aleppo);
    mrpMonth($project, '2026-09-01', MonthlyReportStatus::Validated);
    mrpMonth($project, '2026-10-01', MonthlyReportStatus::Submitted);
    $meal = userWithRole('MEAL', insertHrEmployee(firstNameEn: 'Huda', lastNameEn: 'Meal'));
    $monthTrigger = fn (string $choice): string => 'aria-label="'.e(__('common.filter_choice', ['label' => __('monthly_reports.month'), 'choice' => $choice])).'"';

    Livewire::actingAs($meal)->test('pages::monthly-reports.index')
        ->set('month', '2026-10')
        ->set('status', 'validated')
        ->assertSeeHtml($monthTrigger('October 2026'))
        ->assertDontSeeHtml($monthTrigger(__('monthly_reports.any_month')));
});

test('the queue never lists a draft, even for every status', function () {
    $project = mrpProject($this->aleppo);
    $draft = mrpMonth($project, '2026-11-01');
    $submitted = mrpMonth($project, '2026-10-01', MonthlyReportStatus::Submitted);
    $validated = mrpMonth($project, '2026-09-01', MonthlyReportStatus::Validated);

    Livewire::actingAs(userWithRole('MEAL', insertHrEmployee(firstNameEn: 'Huda', lastNameEn: 'Meal')))
        ->test('pages::monthly-reports.index')
        ->set('status', 'all')
        ->assertSeeHtml('data-validate-row="'.$submitted->id.'"')
        ->assertSeeHtml('data-validate-row="'.$validated->id.'"')
        ->assertDontSeeHtml('data-validate-row="'.$draft->id.'"');
});

test('"Not seen yet" marks only a submitted report still waiting for its first look', function () {
    $project = mrpProject($this->aleppo);
    mrpMonth($project, '2026-09-01', MonthlyReportStatus::Validated, ['seen_by_meal_at' => null]);
    mrpMonth($project, '2026-10-01', MonthlyReportStatus::Submitted, ['seen_by_meal_at' => null]);

    Livewire::actingAs(userWithRole('MEAL', insertHrEmployee(firstNameEn: 'Huda', lastNameEn: 'Meal')))
        ->test('pages::monthly-reports.index')
        ->assertSeeText(__('monthly_reports.not_seen_yet'))
        ->set('status', 'validated')
        ->assertDontSeeText(__('monthly_reports.not_seen_yet'));
});

test('active filters offer one way back, and an empty result does not mention a search', function () {
    mrpMonth(mrpProject($this->aleppo), '2026-09-01', MonthlyReportStatus::Submitted);

    Livewire::actingAs(userWithRole('MEAL', insertHrEmployee(firstNameEn: 'Huda', lastNameEn: 'Meal')))
        ->test('pages::monthly-reports.index')
        ->set('month', '2026-01')
        ->assertSeeText(__('desk.register.clear'))
        ->assertDontSeeText(__('common.reset_filters'))
        ->assertSeeText(__('monthly_reports.empty_filtered'))
        ->assertSeeText(__('common.change_the_filters_and_try_again'))
        ->assertDontSeeText(__('common.change_the_search_or_filters_and_try_again'));
});

test('each month\'s view link on "My reports" names its project', function () {
    $this->travelTo('2026-11-20 10:00:00');
    mrpMonth(mrpProject($this->aleppo, name: 'Hope Center Aleppo'), '2026-09-01');
    mrpMonth(mrpProject($this->aleppo, name: 'Micro Aleppo'), '2026-09-01');

    Livewire::actingAs($this->owner)->test('pages::monthly-reports.index')
        ->assertSeeHtml('aria-label="'.e(__('common.view_name', ['name' => 'Hope Center Aleppo · September 2026'])).'"')
        ->assertSeeHtml('aria-label="'.e(__('common.view_name', ['name' => 'Micro Aleppo · September 2026'])).'"');
});

test('someone who cannot validate is not offered an empty "To validate" tab', function () {
    $this->travelTo('2026-11-20 10:00:00');
    mrpMonth(mrpProject($this->aleppo), '2026-09-01');

    Livewire::actingAs($this->owner)->test('pages::monthly-reports.index')
        ->assertDontSeeHtml('data-desk-register-view="validate"')
        ->assertSet('tab', 'mine');

    Livewire::withQueryParams(['tab' => 'validate'])->actingAs($this->owner)->test('pages::monthly-reports.index')
        ->assertSet('tab', 'mine');

    Livewire::actingAs(userWithRole('MEAL', insertHrEmployee(firstNameEn: 'Huda', lastNameEn: 'Meal')))
        ->test('pages::monthly-reports.index')
        ->assertSeeHtml('data-desk-register-view="validate"');
});

/*
| Page redesign, 6 Nov 2026: a figures line over each tab, a "Needs you"
| list at the top of "My reports", and how long each submitted report has
| been waiting in the validation queue.
*/

/**
 * The preparer's months of one project on 20 Nov 2026: September with MEAL,
 * October returned (so overdue), November a draft, December not started.
 *
 * @return array{october: MonthlyReport, november: MonthlyReport}
 */
function mrpPreparerMonths(Project $project): array
{
    test()->travelTo('2026-11-20 10:00:00');
    mrpMonth($project, '2026-08-01', MonthlyReportStatus::Validated);
    mrpMonth($project, '2026-09-01', MonthlyReportStatus::Submitted);
    $october = mrpMonth($project, '2026-10-01', attributes: ['returned_at' => '2026-11-05 09:00:00', 'returned_by' => User::factory(), 'return_reason' => 'Figures do not match the attendance sheets.']);
    $november = mrpMonth($project, '2026-11-01');
    mrpMonth($project, '2026-12-01');

    return ['october' => $october, 'november' => $november];
}

test('"Needs you" lists the months the preparer can fill, overdue first, above the project threads', function () {
    mrpPreparerMonths(mrpProject($this->aleppo));

    $html = Livewire::actingAs($this->owner)->test('pages::monthly-reports.index')
        ->assertSeeText(__('monthly_reports.needs_you'))
        ->html();

    $october = strpos($html, 'data-needs-you-item="2026-10"');
    $november = strpos($html, 'data-needs-you-item="2026-11"');

    expect($october)->not->toBeFalse()
        ->and($november)->not->toBeFalse()
        ->and($october)->toBeLessThan($november)
        ->and($html)->not->toContain('data-needs-you-item="2026-09"')
        ->and($html)->not->toContain('data-needs-you-item="2026-12"')
        ->and(strpos($html, 'data-needs-you'))->toBeLessThan(strpos($html, 'data-month="2026-08"'));
});

test('someone who reads the months but cannot fill them has no "Needs you" list', function () {
    mrpPreparerMonths(mrpProject($this->aleppo));

    Livewire::actingAs(userWithRole('Team Member', $this->ownerEmployee))->test('pages::monthly-reports.index')
        ->assertSeeHtml('data-month="2026-11"')
        ->assertDontSeeHtml('data-needs-you');
});

test('the figures line sums up "My reports"', function () {
    mrpPreparerMonths(mrpProject($this->aleppo));

    Livewire::actingAs($this->owner)->test('pages::monthly-reports.index')
        ->assertSeeHtml('data-figure="to_fill" data-value="2"')
        ->assertSeeHtml('data-figure="overdue" data-value="1"')
        ->assertSeeHtml('data-figure="with_meal" data-value="1"')
        ->assertSeeHtml('data-figure="validated" data-value="1"');
});

test('the figures line sums up the validation queue, whatever the filters', function () {
    $this->travelTo('2026-11-20 10:00:00');
    $project = mrpProject($this->aleppo);
    mrpMonth($project, '2026-09-01', MonthlyReportStatus::Validated, ['seen_by_meal_at' => '2026-10-03 09:00:00']);
    mrpMonth($project, '2026-10-01', MonthlyReportStatus::Submitted, ['seen_by_meal_at' => null]);
    mrpMonth($project, '2026-11-01');

    Livewire::actingAs(userWithRole('MEAL', insertHrEmployee(firstNameEn: 'Huda', lastNameEn: 'Meal')))
        ->test('pages::monthly-reports.index')
        ->assertSeeHtml('data-figure="waiting" data-value="1"')
        ->assertSeeHtml('data-figure="not_seen" data-value="1"')
        ->assertSeeHtml('data-figure="validated" data-value="1"')
        ->set('month', '2026-09')
        ->assertSeeHtml('data-figure="waiting" data-value="1"');
});

test('a submitted report in the queue says how long it has waited', function () {
    $this->travelTo('2026-11-20 10:00:00');
    $project = mrpProject($this->aleppo);
    $waiting = mrpMonth($project, '2026-10-01', MonthlyReportStatus::Submitted, ['submitted_at' => '2026-11-17 09:00:00']);
    $validated = mrpMonth($project, '2026-09-01', MonthlyReportStatus::Validated);

    Livewire::actingAs(userWithRole('MEAL', insertHrEmployee(firstNameEn: 'Huda', lastNameEn: 'Meal')))
        ->test('pages::monthly-reports.index')
        ->set('status', 'all')
        ->assertSeeHtml('data-waiting="'.$waiting->id.'"')
        ->assertSeeText(trans_choice('monthly_reports.waiting_days', 3, ['count' => 3]))
        ->assertDontSeeHtml('data-waiting="'.$validated->id.'"');
});

/*
| Fill that did nothing, 6 Nov 2026: a project with no start or end date has
| no period, so its months can never be opened. The button was offered
| anyway and its refusal was written to an error nobody displayed.
*/

test('a month of a project with no start or end date offers no Fill and says why', function () {
    $this->travelTo('2026-11-20 10:00:00');
    $project = mrpProject($this->aleppo);
    $november = mrpMonth($project, '2026-11-01');
    DB::table('projects')->where('id', $project->id)->update(['start_date' => null, 'end_date' => null]);

    Livewire::actingAs($this->owner)->test('pages::monthly-reports.index')
        ->assertSeeHtml('data-month="2026-11"')
        ->assertDontSeeHtml('data-fill="'.$november->id.'"')
        ->assertSeeText(__('monthly_reports.project_has_no_period'))
        ->assertDontSeeHtml('data-needs-you');
});

test('a Fill the server refuses says why instead of doing nothing', function () {
    $this->travelTo('2026-11-20 10:00:00');
    $project = mrpProject($this->aleppo);
    $november = mrpMonth($project, '2026-11-01');
    $page = Livewire::actingAs($this->owner)->test('pages::monthly-reports.index');
    DB::table('projects')->where('id', $project->id)->update(['start_date' => null, 'end_date' => null]);

    $page->call('fillMonth', $november->id)
        ->assertNoRedirect()
        ->assertDispatched('toast-show', fn (string $event, array $params): bool => ($params['slots']['text'] ?? null) === __('monthly_reports.project_has_no_period'));
});

/*
| Layout redesign, 6 Nov 2026: big figures that filter the list, a month
| strip per project with the validated months folded away, one notice per
| project instead of a line under every month, and the queue grouped by
| project.
*/

test('the big figures filter "My reports" to the months they count, and a second click clears it', function () {
    mrpPreparerMonths(mrpProject($this->aleppo));

    Livewire::actingAs($this->owner)->test('pages::monthly-reports.index')
        ->call('showOnly', 'overdue')
        ->assertSet('show', 'overdue')
        ->assertSeeHtml('data-figure="overdue" data-value="1" aria-pressed="true"')
        ->assertSeeHtml('data-month="2026-10"')
        ->assertDontSeeHtml('data-month="2026-11"')
        ->assertDontSeeHtml('data-month="2026-08"')
        ->assertDontSeeHtml('data-needs-you')
        ->call('showOnly', 'overdue')
        ->assertSet('show', '')
        ->assertSeeHtml('data-month="2026-11"');
});

test('a project shows its months as a strip and folds the validated ones away', function () {
    $project = mrpProject($this->aleppo);
    mrpPreparerMonths($project);

    Livewire::actingAs($this->owner)->test('pages::monthly-reports.index')
        ->assertSeeHtml('data-month-strip="'.$project->id.'"')
        ->assertSeeHtml('data-strip-month="2026-08" data-strip-state="validated"')
        ->assertSeeHtml('data-strip-month="2026-10" data-strip-state="overdue"')
        ->assertSeeHtml('data-validated-fold="'.$project->id.'"')
        ->call('showOnly', 'validated')
        ->assertDontSeeHtml('data-validated-fold="'.$project->id.'"')
        ->assertSeeHtml('data-month="2026-08"');
});

test('a project with no dates says so once, with a way to the project', function () {
    $this->travelTo('2026-11-20 10:00:00');
    $project = mrpProject($this->aleppo);
    mrpMonth($project, '2026-10-01');
    mrpMonth($project, '2026-11-01');
    DB::table('projects')->where('id', $project->id)->update(['start_date' => null, 'end_date' => null]);

    $html = Livewire::actingAs($this->owner)->test('pages::monthly-reports.index')
        ->assertSeeHtml('data-project-notice="'.$project->id.'"')
        ->assertSeeHtml('href="'.route('projects.show', $project).'"')
        ->html();

    expect(substr_count($html, e(__('monthly_reports.project_has_no_period'))))->toBe(1);
});

test('the validation queue groups its rows by project, and its figures switch the status', function () {
    $this->travelTo('2026-11-20 10:00:00');
    $first = mrpProject($this->aleppo, name: 'Aleppo Center');
    $second = mrpProject($this->aleppo, name: 'Micro Aleppo');
    $firstReport = mrpMonth($first, '2026-10-01', MonthlyReportStatus::Submitted);
    $secondReport = mrpMonth($second, '2026-10-01', MonthlyReportStatus::Submitted);
    mrpMonth($first, '2026-09-01', MonthlyReportStatus::Validated);

    $page = Livewire::actingAs(userWithRole('MEAL', insertHrEmployee(firstNameEn: 'Huda', lastNameEn: 'Meal')))
        ->test('pages::monthly-reports.index');
    $html = $page->html();

    expect(strpos($html, 'data-queue-project="'.$first->id.'"'))->toBeLessThan(strpos($html, 'data-validate-row="'.$firstReport->id.'"'))
        ->and(strpos($html, 'data-validate-row="'.$firstReport->id.'"'))->toBeLessThan(strpos($html, 'data-queue-project="'.$second->id.'"'))
        ->and(strpos($html, 'data-queue-project="'.$second->id.'"'))->toBeLessThan(strpos($html, 'data-validate-row="'.$secondReport->id.'"'));

    $page->call('showOnly', 'validated')->assertSet('status', 'validated')
        ->call('showOnly', 'waiting')->assertSet('status', 'submitted');
});
