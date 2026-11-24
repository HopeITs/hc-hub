<?php

use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Http\Controllers\Attendance\AttendanceUploadController;
use Modules\Hr\Models\AttendanceDayDecision;
use Modules\Hr\Models\AttendanceImport;
use Modules\Hr\Models\AttendancePunch;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
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
        ->assertSet('showDayModal', true)
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

test('the pages need their permission, and the print shows only the viewer\'s city as a draft', function () {
    $mine = ($this->volunteer)();
    $other = ($this->volunteer)(City::factory()->create());
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
        ->assertSee('Monthly TimeSheet')
        ->assertSee('مسودة');
});

test('a print link without a month opens the month the page opens on', function () {
    $mine = ($this->volunteer)();
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
    ($this->volunteer)();
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
