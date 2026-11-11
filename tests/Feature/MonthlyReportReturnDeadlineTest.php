<?php

use App\Models\Activity;
use App\Notifications\Alerts\DeadlineCatalog;
use Carbon\CarbonImmutable;
use Livewire\Livewire;
use Modules\Hr\Models\Holiday;
use Modules\Tracking\Actions\MonthlyReports\DecideMonthlyReportReopen;
use Modules\Tracking\Actions\MonthlyReports\ReturnMonthlyReport;
use Modules\Tracking\Enums\MonthlyReportStatus;
use Modules\Tracking\Models\MealCoverageAssignment;
use Modules\Tracking\Models\MonthlyReport;
use Modules\Tracking\Models\MonthlyReportLine;
use Modules\Tracking\Models\MonthlyReportReopenRequest;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Support\MonthlyReportPeriods;

/*
| A report sent back after its own deadline (returned by its validator, or
| reopened by a granted request) gets a new one: the next working day,
| skipping the project's weekend and HR holidays (Managers UAT, 2026-11-10):
| national ones, and those of the project's program and governorate.
| It is late only once that day has ended. The month's own deadline skips
| HR holidays too.
|
| The October 2026 report of a Monday-to-Friday project is due on Friday
| 6 November (five working days after 31 October).
*/

beforeEach(function () {
    $this->city = insertHrCity('Aleppo');
    $this->center = insertHrCenter($this->city);
    $this->ownerEmployee = insertHrEmployee(firstNameEn: 'Omar', lastNameEn: 'Owner');
    $this->owner = userWithRole('Managers', $this->ownerEmployee);
    $this->officerEmployee = insertHrEmployee(firstNameEn: 'Nour', lastNameEn: 'Officer');
    Activity::withoutRecording(fn () => MealCoverageAssignment::factory()->forCity($this->city)->create(['employee_id' => $this->officerEmployee]));
    $this->officer = userWithRole('MEAL', $this->officerEmployee);

    $this->project = Activity::withoutRecording(fn (): Project => Project::factory()->active()->create([
        'city_id' => $this->city,
        'center_id' => $this->center,
        'user_id' => $this->ownerEmployee,
        'start_date' => '2026-10-01',
        'end_date' => '2026-12-31',
        'working_days' => [1, 2, 3, 4, 5],
    ]));
    $this->report = Activity::withoutRecording(function (): MonthlyReport {
        $report = MonthlyReport::factory()->submitted()->create([
            'project_id' => $this->project->id, 'period' => '2026-10-01', 'submitted_by' => $this->owner->id, 'prepared_by' => $this->owner->id,
        ]);
        MonthlyReportLine::factory()->create(['monthly_report_id' => $report->id]);

        return $report;
    });
});

/**
 * The officer returns the October report at the given moment.
 */
function returnOctoberReport(string $at): MonthlyReport
{
    test()->travelTo($at);
    test()->actingAs(test()->officer);

    return app(ReturnMonthlyReport::class)->handle(test()->report->fresh(), MonthlyReportStatus::Submitted, 'Please recheck the totals.');
}

test('a report returned on or before its own deadline keeps that deadline', function () {
    expect(returnOctoberReport('2026-11-06 15:00:00')->revised_due_on)->toBeNull();
});

test('a report returned after its deadline is due the next working day', function (string $returnedAt, string $dueOn) {
    expect(returnOctoberReport($returnedAt)->revised_due_on?->toDateString())->toBe($dueOn);
})->with([
    'returned on a Thursday, due Friday' => ['2026-11-12 10:00:00', '2026-11-13'],
    'returned on a Friday, the weekend is skipped' => ['2026-11-13 16:30:00', '2026-11-16'],
    'returned on a Saturday, due Monday' => ['2026-11-14 09:00:00', '2026-11-16'],
]);

/**
 * Record an HR holiday: national, or for the given program × governorate pairs.
 *
 * @param  list<array{0: int, 1: int}>  $pairs
 */
function recordHoliday(string $startDate, ?string $endDate = null, array $pairs = [], bool $isRecurring = false, string $kind = Holiday::KIND_OFFICIAL): Holiday
{
    $holiday = Holiday::query()->create([
        'name_en' => 'Holiday',
        'name_ar' => 'عطلة',
        'start_date' => $startDate,
        'end_date' => $endDate ?? $startDate,
        'is_recurring' => $isRecurring,
        'scope' => $pairs === [] ? Holiday::SCOPE_NATIONAL : Holiday::SCOPE_PROJECTS,
        'kind' => $kind,
    ]);

    foreach ($pairs as [$programId, $cityId]) {
        $holiday->projects()->create(['program_id' => $programId, 'city_id' => $cityId]);
    }

    return $holiday;
}

test('an HR holiday that reaches the project is skipped like a weekend', function (bool $national) {
    recordHoliday('2026-11-16', '2026-11-17', $national ? [] : [[$this->project->program_id, $this->city]]);

    expect(returnOctoberReport('2026-11-13 16:30:00')->revised_due_on?->toDateString())->toBe('2026-11-18');
})->with([
    'a national holiday' => [true],
    'a holiday of the program and governorate of the project' => [false],
]);

test('a recurring holiday counts every year; a holiday of another governorate and the birthday holiday do not count', function () {
    recordHoliday('2020-11-16', isRecurring: true);
    recordHoliday('2026-11-17', pairs: [[$this->project->program_id, insertHrCity('Homs', 'حمص')]]);
    recordHoliday('2026-01-01', '2026-12-31', kind: Holiday::KIND_BIRTHDAY);

    expect(returnOctoberReport('2026-11-13 16:30:00')->revised_due_on?->toDateString())->toBe('2026-11-17');
});

test('the returned report is not overdue on its new deadline and is overdue the day after', function () {
    returnOctoberReport('2026-11-12 10:00:00');
    $page = fn () => Livewire::actingAs($this->owner)->test('tracking::monthly-reports.index');

    $this->travelTo('2026-11-13 16:59:00');
    $page()->assertSeeHtml('data-month="2026-10" data-month-state="returned"')->assertDontSeeHtml('data-overdue="2026-10"');

    $this->travelTo('2026-11-14 08:00:00');
    $page()->assertSeeHtml('data-overdue="2026-10"');
});

test('alerts and the desk matrix follow the new deadline', function () {
    returnOctoberReport('2026-11-12 10:00:00');

    $this->travelTo('2026-11-13 10:00:00');
    $item = app(DeadlineCatalog::class)->items(CarbonImmutable::parse('2026-11-30'))->first(fn ($item): bool => $item->subjectType === 'monthly_report' && $item->subjectId === $this->report->id);
    $matrix = Livewire::actingAs($this->owner)->test('desk.panels.matrix', ['size' => 'XL']);

    expect($item?->dueDate->toDateString())->toBe('2026-11-13')
        ->and($matrix->instance()->stateOf($this->report->fresh()))->toBe('returned');

    $this->travelTo('2026-11-14 10:00:00');

    expect($matrix->instance()->stateOf($this->report->fresh()))->toBe('late');
});

/**
 * The October report validated, with a pending reopen request of its owner;
 * the MEAL Manager slot holder grants or denies it at the given moment.
 */
function decideOctoberReopen(string $at, bool $grant): MonthlyReport
{
    Activity::withoutRecording(function (): void {
        test()->report->forceFill(['status' => MonthlyReportStatus::Validated, 'validated_at' => '2026-11-07 10:00:00'])->save();
        MonthlyReportReopenRequest::factory()->create(['monthly_report_id' => test()->report->id, 'requested_by' => test()->owner->id]);
    });
    $decider = mealManager();
    test()->travelTo($at);
    test()->actingAs($decider);

    app(DecideMonthlyReportReopen::class)->handle(
        test()->report->fresh(),
        MonthlyReportReopenRequest::query()->sole()->id,
        MonthlyReportStatus::Validated,
        $grant,
        $grant ? null : 'The totals match the attendance sheets.',
    );

    return test()->report->fresh();
}

test('a reopen granted after the deadline is due the next working day, and a denial changes no deadline', function () {
    expect(decideOctoberReopen('2026-11-13 16:30:00', grant: true))
        ->status->toBe(MonthlyReportStatus::Draft)
        ->revised_due_on?->toDateString()->toBe('2026-11-16');
});

test('a denied reopen keeps the report validated with no new deadline', function () {
    expect(decideOctoberReopen('2026-11-13 16:30:00', grant: false))
        ->status->toBe(MonthlyReportStatus::Validated)
        ->revised_due_on->toBeNull();
});

test('a reopened report is a draft on the desk matrix until its new deadline ends, then late', function () {
    decideOctoberReopen('2026-11-13 16:30:00', grant: true);
    $matrix = Livewire::actingAs($this->owner)->test('desk.panels.matrix', ['size' => 'XL']);

    $this->travelTo('2026-11-16 12:00:00');
    expect($matrix->instance()->stateOf($this->report->fresh()))->toBe('draft');

    $this->travelTo('2026-11-17 08:00:00');
    expect($matrix->instance()->stateOf($this->report->fresh()))->toBe('late');
});

test('the month\'s own deadline skips HR holidays', function () {
    recordHoliday('2026-11-03');

    // Five working days after 31 October without Tuesday 3 November: Monday 9 November.
    expect(MonthlyReportPeriods::for($this->project)->dueDate(CarbonImmutable::parse('2026-10-01'))->toDateString())->toBe('2026-11-09');
});

test('a holiday on the month\'s last day moves its last working day, so the report may be submitted the day before', function (bool $holiday) {
    if ($holiday) {
        recordHoliday('2026-11-30');
    }
    $november = Activity::withoutRecording(fn (): MonthlyReport => MonthlyReport::factory()->create(['project_id' => $this->project->id, 'period' => '2026-11-01']));
    $this->travelTo('2026-11-27 10:00:00');

    // Friday 27 November; Monday 30 November is the month's last day.
    expect(MonthlyReportPeriods::for($this->project)->canSubmit($november) === __('monthly_reports.submit_too_early'))->toBe(! $holiday);
})->with(['a working Monday 30th' => false, 'a holiday on Monday 30th' => true]);
