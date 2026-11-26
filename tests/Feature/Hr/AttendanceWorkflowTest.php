<?php

use App\Notifications\AppNotification;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Console\SendAttendanceRemindersCommand;
use Modules\Hr\Models\AttendanceDayDecision;
use Modules\Hr\Models\AttendanceImport;
use Modules\Hr\Models\AttendanceMonth;
use Modules\Hr\Models\AttendancePunch;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Attendance\AttendanceImporter;
use Modules\Hr\Support\Attendance\MonthWorkflow;
use Modules\Hr\Support\Attendance\WorkflowRefused;
use Spatie\Permission\Models\Permission;

/*
| A month goes open -> (confirmed) -> approved by the person's manager -> locked
| by HR, and can be reopened by HR. A month with days still waiting for an
| answer cannot be approved; a new answer or new punches take an approval
| back; a locked month takes no new punches. The signed paper is kept with the
| month. In the first days of a month, managers are reminded to approve and HR
| to lock, once a day.
*/

beforeEach(function () {
    foreach (['attendance.view-own', 'attendance.view', 'attendance.manage'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->city = City::factory()->create();
    $this->september = CarbonImmutable::create(2026, 9, 1);

    $managerPosition = Position::factory()->create(['city_id' => $this->city->id]);
    $managerEmployee = Employee::factory()->create(['city_id' => $this->city->id]);
    Contract::factory()->create(['employee_id' => $managerEmployee->id, 'position_id' => $managerPosition->id, 'weekly_hours' => 40, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]);
    $this->manager = User::factory()->role('Managers')->create(['employee_id' => $managerEmployee->id]);
    $this->manager->givePermissionTo(['attendance.view-own', 'attendance.view']);
    $this->manager->refresh();

    // Mon 7 and Tue 8 September are the whole contract, both worked: the month is ready.
    $this->report = Employee::factory()->create(['city_id' => $this->city->id]);
    Contract::factory()->create([
        'employee_id' => $this->report->id,
        'position_id' => Position::factory()->create(['city_id' => $this->city->id, 'direct_manager_id' => $managerPosition->id])->id,
        'weekly_hours' => 40,
        'start_date' => '2026-09-07',
        'end_date' => '2026-09-08',
        'is_active' => true,
    ]);
    $this->reportUser = User::factory()->create(['employee_id' => $this->report->id]);
    $this->reportUser->givePermissionTo('attendance.view-own');
    $this->reportUser->refresh();

    $this->hr = User::factory()->role('HR-'.strtoupper($this->city->slug))->create(['employee_id' => Employee::factory()->create(['city_id' => $this->city->id])->id]);
    $this->hr->givePermissionTo(['attendance.view-own', 'attendance.view', 'attendance.manage']);
    $this->hr->refresh();

    $this->punch = function (string $at, ?Employee $employee = null): void {
        $employee ??= $this->report;
        AttendancePunch::query()->create([
            'import_id' => AttendanceImport::query()->firstOrCreate(['file_name' => 'test.xls'])->id,
            'device_no' => (string) $employee->id,
            'employee_id' => $employee->id,
            'punched_at' => $at,
        ]);
    };

    ($this->punch)('2026-09-07 08:00:00');
    ($this->punch)('2026-09-07 17:00:00'); // 9 hours: 1 hour of overtime
    ($this->punch)('2026-09-08 09:00:00');
    ($this->punch)('2026-09-08 17:00:00');

    $this->workflow = app(MonthWorkflow::class);
    $this->state = fn (): string => AttendanceMonth::for($this->report, $this->september)->state();
});

test('the manager approves a ready month with its overtime, and HR locks and reopens it', function () {
    $approved = $this->workflow->approve($this->manager, $this->report, $this->september);

    expect($approved->overtime_hours_approved)->toBe(1.0)
        ->and(($this->state)())->toBe(AttendanceMonth::APPROVED)
        ->and(fn () => $this->workflow->lock($this->manager, $this->report, $this->september))->toThrow(WorkflowRefused::class);

    $this->workflow->lock($this->hr, $this->report, $this->september);
    expect(($this->state)())->toBe(AttendanceMonth::LOCKED);

    $this->workflow->reopen($this->hr, $this->report, $this->september);
    expect(($this->state)())->toBe(AttendanceMonth::OPEN);
});

test('nobody approves their own month, and the employee confirms only their own', function () {
    $this->workflow->confirm($this->reportUser, $this->report, $this->september);

    expect(($this->state)())->toBe(AttendanceMonth::CONFIRMED)
        ->and(fn () => $this->workflow->approve($this->reportUser, $this->report, $this->september))->toThrow(WorkflowRefused::class)
        ->and(fn () => $this->workflow->confirm($this->manager, $this->report, $this->september))->toThrow(WorkflowRefused::class);
});

test('days that wait for an answer block the approval, and an answer takes an approval back', function () {
    AttendancePunch::query()->whereDate('punched_at', '2026-09-08')->delete();

    expect(fn () => $this->workflow->approve($this->manager, $this->report, $this->september))
        ->toThrow(WorkflowRefused::class, 'hr_attendance.workflow.refused.days-waiting');

    Livewire::actingAs($this->manager)->test('hr::attendance.timesheets')
        ->set('month', '2026-09')
        ->call('openDay', $this->report->id, '2026-09-08')
        ->set('dayKind', AttendanceDayDecision::WORKED)
        ->call('saveDecision')
        ->assertHasNoErrors();

    $this->workflow->approve($this->manager, $this->report, $this->september);

    Livewire::actingAs($this->manager)->test('hr::attendance.timesheets')
        ->set('month', '2026-09')
        ->call('openDay', $this->report->id, '2026-09-08')
        ->set('dayKind', AttendanceDayDecision::ABSENT)
        ->call('saveDecision');

    expect(($this->state)())->toBe(AttendanceMonth::OPEN);
});

test('a manager cannot answer a day of their own month', function () {
    ($this->punch)('2026-09-09 09:00:00', $this->manager->employee);

    Livewire::actingAs($this->manager)->test('hr::attendance.timesheets')
        ->set('month', '2026-09')
        ->call('openDay', $this->manager->employee_id, '2026-09-09')
        ->assertForbidden();
});

test('new punches withdraw an approval, and a locked month takes none', function () {
    $csv = tempnam(sys_get_temp_dir(), 'att').'.csv';
    // Saturday 5 September: a day the contract does not cover, so a report for it replaces nothing that counts.
    file_put_contents($csv, "AC-No,Name,Time\n{$this->report->id},x,05/09/2026 12:00\n");

    $this->workflow->approve($this->manager, $this->report, $this->september);
    app(AttendanceImporter::class)->import($csv, 'late.csv', null);
    expect(($this->state)())->toBe(AttendanceMonth::OPEN);

    $this->workflow->approve($this->manager, $this->report, $this->september);
    $this->workflow->lock($this->hr, $this->report, $this->september);
    file_put_contents($csv, "AC-No,Name,Time\n{$this->report->id},x,05/09/2026 12:30\n");
    $import = app(AttendanceImporter::class)->import($csv, 'later.csv', null);

    expect($import->punches_new)->toBe(0)
        ->and($import->punches_locked)->toBe(1)
        ->and(($this->state)())->toBe(AttendanceMonth::LOCKED);
});

test('the page steps and the bulk approval follow the same rules', function () {
    Livewire::actingAs($this->reportUser)->test('hr::attendance.timesheets')
        ->set('month', '2026-09')
        ->call('approveMonth', $this->report->id);

    expect(($this->state)())->toBe(AttendanceMonth::OPEN);

    Livewire::actingAs($this->manager)->test('hr::attendance.timesheets')
        ->set('month', '2026-09')
        ->call('approveAllReady');

    expect(($this->state)())->toBe(AttendanceMonth::APPROVED);

    Livewire::actingAs($this->hr)->test('hr::attendance.timesheets')
        ->set('month', '2026-09')
        ->call('lockAllApproved');

    expect(($this->state)())->toBe(AttendanceMonth::LOCKED);
});

test('the signed copy is kept privately and opened only within the roster', function () {
    Storage::fake('hr');

    Livewire::actingAs($this->hr)->test('hr::attendance.timesheets')
        ->set('month', '2026-09')
        ->call('openMonth', $this->report->id)
        ->set('signedFile', UploadedFile::fake()->create('signed.pdf', 20, 'application/pdf'))
        ->call('saveSigned')
        ->assertHasNoErrors();

    $record = AttendanceMonth::for($this->report, $this->september);
    $outsider = User::factory()->create(['employee_id' => Employee::factory()->create(['city_id' => City::factory()->create()->id])->id]);
    $outsider->givePermissionTo('attendance.view-own');
    $outsider->refresh();

    expect($record->exists)->toBeTrue();
    $this->actingAs($this->hr)->get(route('hr.attendance.signed', $record->id))->assertOk();
    $this->actingAs($this->reportUser)->get(route('hr.attendance.signed', $record->id))->assertOk();
    $this->actingAs($outsider)->get(route('hr.attendance.signed', $record->id))->assertForbidden();
});

test('reminders go to the manager to approve, then to HR to lock, once a day, and stop when locked', function () {
    $this->travelTo('2026-10-02 08:15:00');
    Notification::fake();
    $keys = fn (User $user): array => Notification::sent($user, AppNotification::class)
        ->map(fn (AppNotification $notification): string => $notification->payload->key)
        ->values()
        ->all();

    $this->artisan(SendAttendanceRemindersCommand::class)->assertSuccessful();
    $this->artisan(SendAttendanceRemindersCommand::class)->assertSuccessful();

    expect($keys($this->manager))->toBe(['hr_attendance.notifications.reminder_approve'])
        ->and($keys($this->hr))->toBe([]);

    $this->workflow->approve($this->manager, $this->report, $this->september);
    $this->travelTo('2026-10-03 08:15:00');
    $this->artisan(SendAttendanceRemindersCommand::class)->assertSuccessful();

    expect($keys($this->hr))->toBe(['hr_attendance.notifications.reminder_lock']);

    $this->workflow->lock($this->hr, $this->report, $this->september);
    $this->travelTo('2026-10-04 08:15:00');
    $this->artisan(SendAttendanceRemindersCommand::class)->assertSuccessful();

    expect($keys($this->hr))->toHaveCount(1)
        ->and($keys($this->manager))->toHaveCount(1);
});
