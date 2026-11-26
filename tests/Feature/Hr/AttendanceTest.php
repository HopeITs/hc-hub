<?php

use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Core\Models\Center;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Http\Controllers\Attendance\AttendanceUploadController;
use Modules\Hr\Models\AttendanceDayDecision;
use Modules\Hr\Models\AttendanceImport;
use Modules\Hr\Models\AttendancePunch;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Holiday;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Attendance\AttendanceImporter;
use Modules\Hr\Support\Attendance\DeviceReportReader;
use Modules\Hr\Support\Attendance\Grouping;
use Modules\Hr\Support\Attendance\Roster;
use Modules\Hr\Support\Attendance\Timesheet;
use Modules\Hr\Support\Attendance\UnreadableReport;
use Spatie\Permission\Models\Permission;

/*
| The device report becomes a monthly timesheet: it is read (the program's own
| raw .xls, or a CSV), stored once, matched to people, turned into worked days,
| overtime and days that wait for an answer, and printed in the form HR signs.
| Who sees whom follows the active role (Roster).
*/

beforeEach(function () {
    foreach (['attendance.view-own', 'attendance.view', 'attendance.manage'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->city = City::factory()->create();
    $this->september = CarbonImmutable::create(2026, 9, 1);

    // A volunteer on a 40-hour week (8 h a day) for the whole of September; weekends are Sun and Sat.
    $this->volunteer = function (?City $city = null, ?Position $position = null): Employee {
        $employee = Employee::factory()->create(['city_id' => ($city ?? $this->city)->id]);
        Contract::factory()->create([
            'employee_id' => $employee->id,
            'position_id' => ($position ?? Position::factory()->create(['city_id' => ($city ?? $this->city)->id]))->id,
            'weekly_hours' => 40,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        return $employee;
    };

    $this->hr = function (City $city, array $permissions = ['attendance.view-own', 'attendance.view', 'attendance.manage']): User {
        $user = User::factory()->role('HR-'.strtoupper($city->slug))->create();
        $user->givePermissionTo($permissions);

        return $user->refresh();
    };

    $this->punch = function (Employee $employee, string $at): void {
        AttendancePunch::query()->create([
            'import_id' => AttendanceImport::query()->firstOrCreate(['file_name' => 'test.xls'])->id,
            'device_no' => (string) $employee->id,
            'employee_id' => $employee->id,
            'punched_at' => $at,
        ]);
    };

    // Every working day of September answered, so the paper may be printed (nothing waits).
    $this->settle = function (Employee $employee): void {
        for ($day = CarbonImmutable::create(2026, 9, 1); $day->month === 9; $day = $day->addDay()) {
            if (! in_array($day->dayOfWeek, [0, 6], true)) {
                AttendanceDayDecision::query()->updateOrCreate(['employee_id' => $employee->id, 'work_date' => $day->toDateString()], ['kind' => AttendanceDayDecision::WORKED]);
            }
        }
    };

    // The program's own old Excel export: a BOF record, then one text record per cell.
    $this->rawReport = function (array $rows): string {
        $bytes = pack('vv', 0x0809, 6).str_repeat("\0", 6);

        foreach ($rows as $r => $cells) {
            foreach ($cells as $c => $text) {
                $bytes .= pack('vv', 0x0004, 8 + strlen($text)).pack('vv', $r, $c)."\0\0\0".chr(strlen($text)).$text;
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'att');
        file_put_contents($path, $bytes);

        return $path;
    };
});

test('the reader reads the raw excel report and a csv, skipping rows it cannot read', function () {
    $report = app(DeviceReportReader::class)->read(($this->rawReport)([
        ['AC-No.', 'No.', 'Name', 'Time', 'State'],
        ['8', '', '8', '01/09/2026 09:02', 'C/In'],
        ['8', '', '8', '01/09/2026 20:30', 'C/In'],
        ['11', '', '11', 'not a time', 'C/In'],
    ]));

    $csv = tempnam(sys_get_temp_dir(), 'att').'.csv';
    file_put_contents($csv, "AC-No,Name,Time\n8,x,02/09/2026 09:03\n");

    expect($report['punches'])->toHaveCount(2)
        ->and($report['invalid'])->toBe(1)
        ->and($report['punches'][0]['device_no'])->toBe('8')
        ->and($report['punches'][1]['at']->format('Y-m-d H:i'))->toBe('2026-09-01 20:30')
        ->and(app(DeviceReportReader::class)->read($csv)['punches'][0]['at']->format('Y-m-d H:i'))->toBe('2026-09-02 09:03');
});

test('a file without the device columns is refused', function () {
    $csv = tempnam(sys_get_temp_dir(), 'att').'.csv';
    file_put_contents($csv, "Name,Salary\nx,1\n");

    app(DeviceReportReader::class)->read($csv);
})->throws(UnreadableReport::class, 'hr_attendance.upload.no-columns');

test('an upload is stored once, matched by employee id, and a linked number takes its punches along', function () {
    $employee = ($this->volunteer)();
    $file = ($this->rawReport)([
        ['AC-No.', 'Name', 'Time'],
        [(string) $employee->id, 'x', '07/09/2026 08:55'],
        [(string) $employee->id, 'x', '07/09/2026 17:10'],
        ['99999', 'y', '07/09/2026 09:00'],
    ]);
    $importer = app(AttendanceImporter::class);

    $first = $importer->import($file, 'test.xls', null);
    $second = $importer->import($file, 'test.xls', null);

    expect($first->punches_new)->toBe(3)
        ->and($second->punches_new)->toBe(0)
        ->and(AttendancePunch::query()->where('employee_id', $employee->id)->count())->toBe(2)
        ->and(AttendancePunch::query()->where('device_no', '99999')->value('employee_id'))->toBeNull();

    $importer->link('99999', $employee, null);

    expect(AttendancePunch::query()->where('employee_id', $employee->id)->count())->toBe(3);
});

test('the timesheet counts worked days, overtime, weekends and leave, and waits for an answer on empty days', function () {
    $employee = ($this->volunteer)();

    ($this->punch)($employee, '2026-09-07 08:55:00'); // Mon: 9h15, 1 hour of overtime
    ($this->punch)($employee, '2026-09-07 18:10:00');
    ($this->punch)($employee, '2026-09-08 09:00:00'); // Tue: an ordinary day
    ($this->punch)($employee, '2026-09-08 17:00:00');
    ($this->punch)($employee, '2026-09-09 09:05:00'); // Wed: one punch; Thu 10: nothing
    ($this->punch)($employee, '2026-09-12 10:00:00'); // Sat: 2 hours on a weekend
    ($this->punch)($employee, '2026-09-12 12:00:00');

    // Fri 11: accepted leave for the whole day.
    LeaveRequest::factory()->for(LeaveBalance::factory()->for($employee->allContracts()->first()))->create([
        'status' => LeaveRequestStatus::Accepted->value,
        'start_at' => '2026-09-11 00:00:00',
        'end_at' => '2026-09-11 23:59:59',
    ]);

    $sheet = app(Timesheet::class)->forEmployee($employee, $this->september);
    $rows = collect($sheet['rows'])->keyBy(fn (array $row): string => $row['date']->toDateString());

    expect($sheet['daily_hours'])->toBe(8.0)
        ->and($rows['2026-09-07']['kind'])->toBe(Timesheet::WORKED)
        ->and($rows['2026-09-07']['overtime_minutes'])->toBe(60)
        ->and($rows['2026-09-08']['overtime_minutes'])->toBe(0)
        ->and($rows['2026-09-09']['kind'])->toBe(Timesheet::ONE_PUNCH)
        ->and($rows['2026-09-09']['working_days'])->toBe(0.0)
        ->and($rows['2026-09-10']['kind'])->toBe(Timesheet::NO_RECORD)
        ->and($rows['2026-09-11']['kind'])->toBe(Timesheet::LEAVE)
        ->and($rows['2026-09-11']['leave_hours'])->toBe(8.0)
        ->and($rows['2026-09-12']['kind'])->toBe(Timesheet::WEEKEND)
        ->and($rows['2026-09-12']['overtime_minutes'])->toBe(120)
        ->and($sheet['totals']['overtime_hours'])->toBe(3.0)
        ->and($sheet['totals']['leave_hours'])->toBe(8.0);

    AttendanceDayDecision::query()->create(['employee_id' => $employee->id, 'work_date' => '2026-09-09', 'kind' => AttendanceDayDecision::WORKED]);
    AttendanceDayDecision::query()->create(['employee_id' => $employee->id, 'work_date' => '2026-09-10', 'kind' => AttendanceDayDecision::ABSENT]);

    $after = collect(app(Timesheet::class)->forEmployee($employee, $this->september)['rows'])->keyBy(fn (array $row): string => $row['date']->toDateString());

    expect($after['2026-09-09']['kind'])->toBe(Timesheet::DECIDED_WORKED)
        ->and($after['2026-09-09']['working_days'])->toBe(1.0)
        ->and($after['2026-09-10']['kind'])->toBe(Timesheet::DECIDED_ABSENT)
        ->and($after['2026-09-10']['leave_hours'])->toBe(8.0);
});

test('days outside the contract are not asked about', function () {
    $employee = ($this->volunteer)();
    $employee->allContracts()->update(['start_date' => '2026-09-15']);

    $rows = collect(app(Timesheet::class)->forEmployee($employee, $this->september)['rows'])->keyBy(fn (array $row): string => $row['date']->toDateString());

    expect($rows['2026-09-10']['kind'])->toBe(Timesheet::OUTSIDE)
        ->and($rows['2026-09-16']['kind'])->toBe(Timesheet::NO_RECORD);
});

test('the roster is the viewer themself, their team, or their city', function () {
    $managerPosition = Position::factory()->create(['city_id' => $this->city->id]);
    $manager = User::factory()->role('Managers')->create(['employee_id' => ($this->volunteer)(null, $managerPosition)->id]);
    $manager->givePermissionTo(['attendance.view-own', 'attendance.view']);
    $manager->refresh();
    $report = ($this->volunteer)(null, Position::factory()->create(['city_id' => $this->city->id, 'direct_manager_id' => $managerPosition->id]));
    $colleague = ($this->volunteer)();
    $elsewhere = ($this->volunteer)(City::factory()->create());

    $staff = User::factory()->create(['employee_id' => $colleague->id]);
    $staff->givePermissionTo('attendance.view-own');
    $staff->refresh();
    $hr = ($this->hr)($this->city);
    $roster = app(Roster::class);
    $ids = fn (User $user): array => $roster->employees($user, $this->september)->modelKeys();

    expect($roster->mode($staff))->toBe(Roster::OWN)
        ->and($ids($staff))->toBe([$colleague->id])
        ->and($roster->mode($manager))->toBe(Roster::TEAM)
        ->and($ids($manager))->toEqualCanonicalizing([$manager->employee_id, $report->id])
        ->and($roster->mode($hr))->toBe(Roster::CITY)
        ->and($ids($hr))->toContain($colleague->id, $report->id)
        ->and($ids($hr))->not->toContain($elsewhere->id);
});

test('people are grouped by city, then by department, with the totals of each', function () {
    $shared = Position::factory()->create(['city_id' => $this->city->id]);
    $first = ($this->volunteer)(null, $shared);
    $second = ($this->volunteer)(null, Position::factory()->create(['city_id' => $this->city->id, 'department_id' => $shared->department_id]));
    $alone = ($this->volunteer)();
    ($this->punch)($first, '2026-09-07 08:00:00');
    $timesheet = app(Timesheet::class);
    $items = collect([$first, $second, $alone])->map(fn (Employee $employee): array => ['employee' => $employee->load('city'), 'sheet' => $timesheet->forEmployee($employee, $this->september)]);

    $blocks = app(Grouping::class)->group($items, Grouping::DEPARTMENT);
    $together = $blocks[0]['groups']->firstWhere('id', $shared->department_id);

    expect($blocks)->toHaveCount(1)
        ->and($blocks[0]['totals']['people'])->toBe(3)
        ->and($blocks[0]['groups'])->toHaveCount(2)
        ->and($together['totals']['people'])->toBe(2)
        ->and($together['totals']['to_decide'])->toBe($items[0]['sheet']['totals']['to_decide'] + $items[1]['sheet']['totals']['to_decide']);
});

test('hr decides a day on the page, and a weekend is never asked about', function () {
    $employee = ($this->volunteer)();
    $hr = ($this->hr)($this->city);

    Livewire::actingAs($hr)->test('hr::attendance.timesheets')
        ->set('month', '2026-09')
        ->call('openDay', $employee->id, '2026-09-10')
        ->assertSet('dayEmployeeId', $employee->id)
        ->assertSet('dayDate', '2026-09-10')
        ->set('dayKind', AttendanceDayDecision::ABSENT)
        ->set('dayNote', 'no call')
        ->call('saveDecision')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('attendance_day_decisions', ['employee_id' => $employee->id, 'kind' => 'absent', 'note' => 'no call', 'decided_by' => $hr->id]);

    Livewire::actingAs($hr)->test('hr::attendance.timesheets')
        ->set('month', '2026-09')
        ->call('openDay', $employee->id, '2026-09-12')
        ->assertForbidden();
});

test('the pages need their permission, and the print shows only the viewer\'s city', function () {
    $mine = ($this->volunteer)();
    $other = ($this->volunteer)(City::factory()->create());
    foreach ([$mine, $other] as $person) {
        ($this->punch)($person, '2026-09-02 08:00:00');
        ($this->settle)($person);
    }
    $nobody = User::factory()->create()->refresh();
    $hr = ($this->hr)($this->city, ['attendance.view-own', 'attendance.view']);

    $this->actingAs($nobody)->get(route('hr.attendance.timesheets'))->assertForbidden();
    $this->actingAs($nobody)->get(route('hr.attendance.print', ['month' => '2026-09']))->assertForbidden();
    $this->actingAs($hr)->get(route('hr.attendance.upload'))->assertForbidden();

    $this->actingAs($hr)->get(route('hr.attendance.timesheets', ['month' => '2026-09']))
        ->assertOk()
        ->assertSee($mine->name);

    $this->actingAs($hr)->get(route('hr.attendance.print', ['month' => '2026-09']))
        ->assertOk()
        ->assertSee($mine->nameForLocale('ar'))
        ->assertDontSee($other->nameForLocale('ar'))
        ->assertSee('Monthly TimeSheet');
});

test('a print link without a month opens the month the page opens on', function () {
    $mine = ($this->volunteer)();
    ($this->punch)($mine, '2026-09-02 08:00:00');
    ($this->settle)($mine);
    $hr = ($this->hr)($this->city, ['attendance.view-own', 'attendance.view']);

    // The Timesheets page opens on the month of ten days ago: early October means September.
    $this->travelTo('2026-10-05 09:00');

    $this->actingAs($hr)->get(route('hr.attendance.print'))
        ->assertOk()
        ->assertSee($mine->nameForLocale('ar'))
        ->assertSee('Monthly TimeSheet');
});

test('the upload page previews the report, then imports it', function () {
    $employee = ($this->volunteer)();
    $hr = ($this->hr)($this->city);
    Storage::fake(AttendanceUploadController::DISK);
    $file = UploadedFile::fake()->createWithContent('report.csv', "AC-No,Name,Time\n{$employee->id},x,07/09/2026 08:55\n{$employee->id},x,07/09/2026 17:10\n");

    $response = $this->actingAs($hr)->post(route('hr.attendance.upload.store'), ['report' => $file])->assertRedirect();
    parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

    Livewire::actingAs($hr)->withQueryParams(['report' => $query['report']])->test('hr::attendance.upload')
        ->assertHasNoErrors()
        ->assertSet('preview.total', 2)
        ->assertSet('preview.unmatched', 0)
        ->call('import');

    expect(AttendancePunch::query()->where('employee_id', $employee->id)->count())->toBe(2)
        ->and(Storage::disk(AttendanceUploadController::DISK)->allFiles())->toBe([]);

    $this->actingAs($hr)->post(route('hr.attendance.upload.store'), ['report' => UploadedFile::fake()->create('report.pdf', 5)])
        ->assertSessionHasErrors('report');
});

test('city hr cannot link a device number to someone in another city', function () {
    $other = ($this->volunteer)(City::factory()->create());
    $hr = ($this->hr)($this->city);

    Livewire::actingAs($hr)->test('hr::attendance.upload')
        ->set('employeeIds.77', (string) $other->id)
        ->call('linkNumber', '77')
        ->assertHasErrors('employeeIds.77');

    $this->assertDatabaseMissing('attendance_device_links', ['device_no' => '77']);
});

test('the sheets read in one batch match the sheet read for each person', function () {
    $first = ($this->volunteer)();
    $second = ($this->volunteer)();
    ($this->punch)($first, '2026-09-07 08:00:00');
    ($this->punch)($first, '2026-09-07 17:30:00');
    ($this->punch)($second, '2026-09-08 09:00:00');
    LeaveRequest::factory()->for(LeaveBalance::factory()->for($second->allContracts()->first()))->create([
        'status' => LeaveRequestStatus::Accepted->value,
        'start_at' => '2026-09-11 00:00:00',
        'end_at' => '2026-09-11 23:59:59',
    ]);
    $timesheet = app(Timesheet::class);

    $batch = $timesheet->forEmployees(collect([$first, $second]), $this->september);

    foreach ([$first, $second] as $employee) {
        $single = $timesheet->forEmployee($employee, $this->september);

        expect($batch[$employee->id]['totals'])->toBe($single['totals'])
            ->and(array_column($batch[$employee->id]['rows'], 'kind'))->toBe(array_column($single['rows'], 'kind'))
            ->and(count($batch[$employee->id]['balances']))->toBe(count($single['balances']));
    }

    expect($batch[$second->id]['totals']['leave_hours'])->toBe(8.0)
        ->and($timesheet->forEmployees(collect(), $this->september))->toBe([]);
});

test('someone who sees every city starts on their own city, and a city link wins', function () {
    $home = City::factory()->create();
    City::factory()->create();
    $hr = User::factory()->role('HR-SYR')->create(['employee_id' => ($this->volunteer)($home)->id]);
    $hr->givePermissionTo(['attendance.view-own', 'attendance.view', 'attendance.manage']);
    $cityHr = ($this->hr)($this->city);

    expect(app(Roster::class)->mode($hr->refresh()))->toBe(Roster::ALL)
        ->and(app(Roster::class)->defaultCityId($hr))->toBe($home->id)
        ->and(app(Roster::class)->defaultCityId($cityHr))->toBeNull();

    Livewire::actingAs($hr)->test('hr::attendance.timesheets')->assertSet('cityId', $home->id);
    Livewire::withQueryParams(['city' => $this->city->id])->actingAs($hr)->test('hr::attendance.timesheets')->assertSet('cityId', $this->city->id);
});

test('groups start folded, and a search or a hand-opened group shows the rows', function () {
    $first = ($this->volunteer)();
    $second = ($this->volunteer)();
    ($this->punch)($first, '2026-09-02 08:00:00');
    ($this->punch)($second, '2026-09-02 08:00:00');
    $hr = ($this->hr)($this->city);

    $page = Livewire::actingAs($hr)->test('hr::attendance.timesheets')
        ->set('month', '2026-09')
        ->set('groupBy', Grouping::DEPARTMENT)
        ->assertSeeHtml('aria-expanded="false"')
        ->assertDontSeeHtml('aria-expanded="true"');

    $page->set('search', (string) $first->id)->assertSeeHtml('aria-expanded="true"');

    $foldKey = $this->city->id.':'.Grouping::DEPARTMENT.'-'.($second->allContracts()->first()->position->department_id ?: 'none');
    $page->set('search', '')
        ->call('toggleGroup', $foldKey, false)
        ->assertSet('collapsed.'.$foldKey, false)
        ->assertSeeHtml('aria-expanded="true"');
});

test('a search opens every group, even one folded by hand, and clearing it brings the choice back', function () {
    $first = ($this->volunteer)();
    $second = ($this->volunteer)();
    ($this->punch)($first, '2026-09-02 08:00:00');
    ($this->punch)($second, '2026-09-02 08:00:00');
    $hr = ($this->hr)($this->city);
    $foldKey = $this->city->id.':'.Grouping::DEPARTMENT.'-'.($first->allContracts()->first()->position->department_id ?: 'none');

    Livewire::actingAs($hr)->test('hr::attendance.timesheets')
        ->set('month', '2026-09')
        ->set('groupBy', Grouping::DEPARTMENT)
        ->call('toggleGroup', $foldKey, true)
        ->assertDontSeeHtml('aria-expanded="true"')
        ->set('search', (string) $first->id)
        ->assertSeeHtml('aria-expanded="true"')
        ->set('search', '')
        ->assertDontSeeHtml('aria-expanded="true"');
});

test('a public holiday or a feast is a day off with its name, never a day to answer', function () {
    $employee = ($this->volunteer)();
    $hr = ($this->hr)($this->city);
    ($this->punch)($employee, '2026-09-02 08:00:00');

    // A three-day feast, for everyone: no punches on those days and nothing to ask.
    Holiday::query()->create([
        'name_en' => 'Eid al-Adha', 'name_ar' => 'عيد الأضحى',
        'start_date' => '2026-09-15', 'end_date' => '2026-09-17',
        'is_recurring' => false, 'scope' => Holiday::SCOPE_NATIONAL, 'kind' => Holiday::KIND_OFFICIAL,
    ]);

    $rows = collect(app(Timesheet::class)->forEmployee($employee, $this->september)['rows'])->keyBy(fn (array $row): string => $row['date']->toDateString());

    foreach (['2026-09-15', '2026-09-16', '2026-09-17'] as $date) {
        expect($rows[$date]['kind'])->toBe(Timesheet::HOLIDAY)
            ->and($rows[$date]['decidable'])->toBeFalse();
    }

    // The person's calendar shows the feast by name, and the day cannot be opened as a question.
    Livewire::actingAs($hr)->test('hr::attendance.timesheets')
        ->set('month', '2026-09')
        ->call('openMonth', $employee->id)
        ->assertSee('عيد الأضحى')
        ->call('openDay', $employee->id, '2026-09-16')
        ->assertForbidden();
});

test("the calendar names the person's own days off and where they come from", function () {
    $employee = ($this->volunteer)();
    $contract = $employee->allContracts()->first();
    $center = Center::factory()->create(['city_id' => $this->city->id, 'weekends' => '0,6']);
    $contract->position->update(['center_id' => $center->id]);
    $hr = ($this->hr)($this->city);
    ($this->punch)($employee, '2026-09-02 08:00:00');

    $placement = app(Timesheet::class)->forEmployee($employee, $this->september)['placement'];

    expect($placement['days_off'])->toBe([0, 6])
        ->and($placement['days_off_from'])->toBe('center');

    // A center whose only day off is Sunday ("0") is a real setting, not "nothing set".
    $center->update(['weekends' => '0']);
    $sheet = app(Timesheet::class)->forEmployee($employee->fresh(), $this->september);
    $rows = collect($sheet['rows'])->keyBy(fn (array $row): string => $row['date']->toDateString());

    expect($sheet['placement']['days_off'])->toBe([0])
        ->and($sheet['placement']['days_off_from'])->toBe('center')
        ->and($rows['2026-09-13']['kind'])->toBe(Timesheet::WEEKEND)
        ->and($rows['2026-09-12']['kind'])->not->toBe(Timesheet::WEEKEND);

    // Days picked on the contract win over the center: Sunday, Tuesday and Thursday are worked.
    $contract->update(['work_weekdays' => '0,2,4']);
    $placement = app(Timesheet::class)->forEmployee($employee->fresh(), $this->september)['placement'];

    expect($placement['days_off'])->toBe([1, 3, 5, 6])
        ->and($placement['days_off_from'])->toBe('contract');

    Livewire::actingAs($hr)->test('hr::attendance.timesheets')
        ->set('month', '2026-09')
        ->call('openMonth', $employee->id)
        ->assertSeeHtml('data-attendance-days-off')
        ->assertSee(__('hr_attendance.timesheets.days-off-from.contract'));
});

test('a day off with punches shows its times on the calendar', function () {
    $employee = ($this->volunteer)();
    $hr = ($this->hr)($this->city);
    ($this->punch)($employee, '2026-09-12 09:00:00'); // Saturday
    ($this->punch)($employee, '2026-09-12 15:30:00');

    Livewire::actingAs($hr)->test('hr::attendance.timesheets')
        ->set('month', '2026-09')
        ->call('openMonth', $employee->id)
        ->assertSee('09:00–15:30')
        ->assertSee(__('hr_attendance.timesheets.worked-off'));
});

test('a day with a leave request is never a day for HR to decide', function () {
    $employee = ($this->volunteer)();
    ($this->punch)($employee, '2026-09-02 08:00:00');
    $balance = LeaveBalance::factory()->for($employee->allContracts()->first())->create();

    foreach ([LeaveRequestStatus::Pending, LeaveRequestStatus::Accepted] as $status) {
        LeaveRequest::query()->delete();
        LeaveRequest::factory()->for($balance)->create([
            'status' => $status->value,
            'start_at' => '2026-09-14 00:00:00',
            'end_at' => '2026-09-14 23:59:59',
        ]);

        $sheet = app(Timesheet::class)->forEmployee($employee->fresh(), $this->september);
        $rows = collect($sheet['rows'])->keyBy(fn (array $row): string => $row['date']->toDateString());

        expect($rows['2026-09-14']['kind'])->toBe(Timesheet::LEAVE)
            ->and($rows['2026-09-14']['decidable'])->toBeFalse()
            ->and($sheet['totals']['leave_pending'])->toBe($status === LeaveRequestStatus::Pending ? 1 : 0);
    }
});

test('the paper is not printed while a day waits or a leave request is unanswered', function () {
    $employee = ($this->volunteer)();
    ($this->punch)($employee, '2026-09-02 08:00:00'); // the other working days have no punch: they wait for HR
    $hr = ($this->hr)($this->city, ['attendance.view-own', 'attendance.view']);

    $this->actingAs($hr)->get(route('hr.attendance.print', ['month' => '2026-09']))->assertStatus(422);

    Livewire::actingAs($hr)->test('hr::attendance.timesheets')
        ->set('month', '2026-09')
        ->assertSeeHtml('data-test="attendance-print-blocked"')
        ->assertDontSeeHtml('data-test="attendance-print-link"');

    // Every day answered: it prints, and the link opens in a new tab.
    ($this->settle)($employee);
    $this->actingAs($hr)->get(route('hr.attendance.print', ['month' => '2026-09']))->assertOk();

    Livewire::actingAs($hr)->test('hr::attendance.timesheets')
        ->set('month', '2026-09')
        ->assertSeeHtmlInOrder(['data-test="attendance-print-link"', 'target="_blank"'])
        ->call('openMonth', $employee->id)
        ->assertSeeHtml('data-test="attendance-print-person"');
});

test('the page lists only people with punches in the month and never goes to a month to come', function () {
    $with = ($this->volunteer)();
    $without = ($this->volunteer)();
    $hr = ($this->hr)($this->city);
    ($this->punch)($with, '2026-09-02 08:00:00');
    ($this->settle)($with);

    // Someone whose center's report is not uploaded yet is not on the page, nor on the print.
    Livewire::actingAs($hr)->test('hr::attendance.timesheets')
        ->set('month', '2026-09')
        ->assertSeeHtml('data-attendance-row="'.$with->id.'"')
        ->assertDontSeeHtml('data-attendance-row="'.$without->id.'"');

    $this->actingAs($hr)->get(route('hr.attendance.print', ['month' => '2026-09']))->assertOk();
    $this->actingAs($hr)->get(route('hr.attendance.print', ['month' => '2026-09', 'employees' => [$without->id]]))->assertNotFound();

    // A month with no punches says so, and the month after the current one never opens.
    $this->travelTo('2026-11-24 10:00:00');
    Livewire::actingAs($hr)->test('hr::attendance.timesheets')
        ->set('month', '2026-10')
        ->assertSee(__('hr_attendance.timesheets.no-punches'))
        ->set('month', '2027-03')
        ->assertSet('month', '2026-11')
        ->call('shiftMonth', 1)
        ->assertSet('month', '2026-11');

    $this->actingAs($hr)->get(route('hr.attendance.print', ['month' => '2027-01']))->assertSessionHasErrors('month');
});

test("a person's calendar answers a day with one button and moves on to the next day that waits", function () {
    $employee = ($this->volunteer)();
    $hr = ($this->hr)($this->city);

    foreach (['2026-09-07', '2026-09-08', '2026-09-09'] as $date) {
        ($this->punch)($employee, $date.' 08:00:00');
        ($this->punch)($employee, $date.' 17:00:00');
    }
    ($this->punch)($employee, '2026-09-10 08:00:00'); // Thu: one punch, so it waits; Fri 11 and the rest wait too

    $page = Livewire::actingAs($hr)->test('hr::attendance.timesheets')
        ->set('month', '2026-09')
        ->call('openMonth', $employee->id)
        ->assertSeeHtml('data-attendance-calendar')
        ->assertSeeHtml('data-attendance-alert')
        ->call('openDay', $employee->id, '2026-09-10')
        ->assertSeeHtml('data-attendance-day-dialog')
        ->call('answerDay', 'absent')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('attendance_day_decisions', ['employee_id' => $employee->id, 'work_date' => '2026-09-10', 'kind' => 'absent', 'decided_by' => $hr->id]);

    // The next day that still waits opens by itself.
    $page->assertSet('dayEmployeeId', $employee->id)->assertNotSet('dayDate', '2026-09-10')->assertNotSet('dayDate', null);

    // A bad answer is refused and nothing is saved for it.
    $page->call('answerDay', 'maybe')->assertHasErrors('dayKind');
});

test('someone who sees only their own month gets the calendar straight away', function () {
    $employee = ($this->volunteer)();
    ($this->punch)($employee, '2026-09-02 08:00:00');
    $staff = User::factory()->create(['employee_id' => $employee->id]);
    $staff->givePermissionTo('attendance.view-own');

    Livewire::actingAs($staff->refresh())->test('hr::attendance.timesheets')
        ->set('month', '2026-09')
        ->assertSeeHtml('data-attendance-month-panel="'.$employee->id.'"')
        ->assertDontSeeHtml('data-test="attendance-back"');
});

test('a new report replaces what was stored for the days it covers, and ties older punches to their employee', function () {
    $employee = ($this->volunteer)();
    $other = ($this->volunteer)();
    $importer = app(AttendanceImporter::class);
    $upload = function (string $rows) use ($importer): AttendanceImport {
        $csv = tempnam(sys_get_temp_dir(), 'att').'.csv';
        file_put_contents($csv, "AC-No.,Time\n".$rows);

        return $importer->import($csv, 'report.csv', null);
    };

    $upload("{$employee->id},01/09/2026 08:00\n{$employee->id},01/09/2026 16:00\n{$employee->id},02/09/2026 08:00\n");
    // Someone the next file does not mention, and a day outside it: both stay.
    ($this->punch)($other, '2026-09-01 08:30:00');
    ($this->punch)($employee, '2026-09-20 08:00:00');

    // The same file twice changes nothing.
    $again = $upload("{$employee->id},01/09/2026 08:00\n{$employee->id},01/09/2026 16:00\n");
    expect([$again->punches_total, $again->punches_new, (int) $again->getAttribute('punches_replaced')])->toBe([2, 0, 0]);

    // The corrected export for 1 September: 08:00 became 08:05. The 2nd is outside it and stays.
    $corrected = $upload("{$employee->id},01/09/2026 08:05\n{$employee->id},01/09/2026 16:00\n");
    $left = AttendancePunch::query()->where('employee_id', $employee->id)->orderBy('punched_at')->pluck('punched_at')->map(fn ($at): string => $at->format('Y-m-d H:i'))->all();

    expect($left)->toBe(['2026-09-01 08:05', '2026-09-01 16:00', '2026-09-02 08:00', '2026-09-20 08:00'])
        ->and(AttendancePunch::query()->where('employee_id', $other->id)->count())->toBe(1)
        ->and([$corrected->punches_total, $corrected->punches_new, (int) $corrected->getAttribute('punches_replaced')])->toBe([2, 1, 1]);

    // Stored earlier when the number matched no one: the next file for those days ties them to the employee.
    $newcomer = ($this->volunteer)();
    $import = AttendanceImport::query()->create(['file_name' => 'old.xls']);
    foreach (['2026-09-03 08:00:00', '2026-09-03 16:00:00'] as $at) {
        AttendancePunch::query()->create(['import_id' => $import->id, 'device_no' => (string) $newcomer->id, 'employee_id' => null, 'punched_at' => $at]);
    }

    $upload("{$newcomer->id},03/09/2026 08:00\n{$newcomer->id},03/09/2026 16:00\n");

    expect(AttendancePunch::query()->where('employee_id', $newcomer->id)->count())->toBe(2);
});
