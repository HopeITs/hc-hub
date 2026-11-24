<?php

use App\Models\Permission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Enums\LeaveTypeCases;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Course;
use Modules\Hr\Models\CourseCategory;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\Education;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Models\LeaveType;
use Modules\Hr\Models\Position;
use Modules\Hr\Models\Relative;
use Modules\Hr\Support\Leave\LeaveRequestPricing;
use Modules\Hr\Support\Recruitment\Access;
use Modules\Hr\Support\Reports\LeaveReport;
use Modules\Hr\Support\Reports\PositionVacanciesReport;
use Modules\Hr\Support\Reports\ProfileCompletenessReport;
use Modules\Hr\Support\Reports\RecruitmentFunnelReport;
use Modules\Hr\Support\Reports\ReportCatalog;
use Modules\Hr\Support\Reports\TrainingReport;
use Modules\Hr\Support\Settings\HrAccess;

/*
| The reports hub and the five reports built from data already in the system.
| "Today" is 2026-11-23. HR and head office only, and each report also needs
| the permission its data already needs.
*/

/**
 * @return list<string>
 */
function allReportPermissions(): array
{
    return ['contracts.view', 'leaves.view-all', 'positions.view', 'employees.view', 'recruitment-applicants.view'];
}

beforeEach(function () {
    Access::flush();
    HrAccess::flush();
    Cache::forget('hub.employees.avatars');
    $this->travelTo('2026-11-23 10:00:00');

    $this->alp = City::factory()->create(['slug' => 'ALP', 'code' => '001', 'name_en' => 'Aleppo', 'name_ar' => 'حلب']);
    $this->hms = City::factory()->create(['slug' => 'HMS', 'code' => '002', 'name_en' => 'Homs', 'name_ar' => 'حمص']);
    $this->program = Program::factory()->create();
    $this->department = Department::factory()->create(['name' => 'Programs']);
    $this->type = ContractType::factory()->create();
    $this->annual = LeaveType::query()->find(LeaveTypeCases::Annual) ?? LeaveType::factory()->create(['id' => LeaveTypeCases::Annual]);
    $this->position = Position::factory()->create([
        'city_id' => $this->alp->id, 'department_id' => $this->department->id, 'program_id' => $this->program->id,
        'title_en' => 'Officer', 'max_emp' => 1, 'exist_emp' => 0,
    ]);

    $this->viewer = fn (string $role, string ...$permissions): User => tap(User::factory()->role($role)->create(), function (User $user) use ($permissions): void {
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
    })->fresh();

    $this->headOffice = fn (): User => ($this->viewer)('Super Admin');

    // An employee of Aleppo with a contract in force today.
    $this->person = function (array $attributes = []): Employee {
        $employee = Employee::factory()->create($attributes + ['city_id' => $this->alp->id, 'is_active' => true, 'username' => fake()->unique()->userName()]);
        Contract::factory()->for($employee)->create([
            'position_id' => $this->position->id, 'contract_type_id' => $this->type->id,
            'is_active' => true, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31',
        ]);

        return $employee;
    };

    $this->makePosition = fn (string $title, array $attributes = []): Position => Position::factory()->create($attributes + [
        'city_id' => $this->alp->id, 'department_id' => $this->department->id, 'program_id' => $this->program->id,
        'title_en' => $title, 'max_emp' => 1, 'exist_emp' => 0,
    ]);
});

// The hub

test('the hub lists only the reports the viewer may open', function () {
    $narrow = ($this->viewer)('HR-ALP', 'contracts.view', 'positions.view');

    Livewire::actingAs($narrow)->test('hr::reports.index')->assertOk()
        ->assertSeeHtml('data-testid="report-card-leaving-reasons"')
        ->assertSeeHtml('data-testid="report-card-vacancies"')
        ->assertDontSeeHtml('data-testid="report-card-training"')
        ->assertDontSeeHtml('data-testid="report-card-leave"')
        ->assertDontSeeHtml('data-testid="report-card-recruitment-funnel"');

    expect(array_keys(ReportCatalog::visibleTo($narrow)))->toBe(['leaving-reasons', 'vacancies']);
});

test('a manager, and an HR user with no matching permission, get nothing', function () {
    $manager = ($this->viewer)('manager', ...allReportPermissions());

    Livewire::actingAs(($this->viewer)('HR-HMS'))->test('hr::reports.index')->assertForbidden();
    Livewire::actingAs($manager)->test('hr::reports.index')->assertForbidden();

    expect(ReportCatalog::visibleTo($manager))->toBe([]);
});

test('every report page refuses a manager even with the permission', function (string $page) {
    Livewire::actingAs(($this->viewer)('manager', ...allReportPermissions()))->test('hr::reports.'.$page)->assertForbidden();
})->with(['recruitment-funnel', 'leave', 'vacancies', 'training', 'profile-completeness', 'leaving-reasons']);

test('every report page renders and exports for head office', function (string $page) {
    Livewire::actingAs(($this->headOffice)())->test('hr::reports.'.$page)->assertOk()->call('export')->assertFileDownloaded();
})->with(['recruitment-funnel', 'leave', 'vacancies', 'training', 'profile-completeness', 'leaving-reasons']);

test('every report opens through its route for head office and not for a manager', function () {
    $routes = ['hr.reports.index', 'hr.reports.leaving-reasons', 'hr.reports.recruitment-funnel', 'hr.reports.leave', 'hr.reports.vacancies', 'hr.reports.training', 'hr.reports.profile-completeness'];
    $manager = ($this->viewer)('manager', ...allReportPermissions());
    $head = ($this->headOffice)();

    foreach ($routes as $name) {
        $this->actingAs($manager)->get(route($name))->assertForbidden();
        $this->actingAs($head)->get(route($name))->assertOk();
    }
});

// Position vacancies

test('vacancies count seats and flag empty, over and critical positions', function () {
    ($this->makePosition)('Coordinator', ['max_emp' => 3, 'exist_emp' => 1, 'is_critical' => true]);
    ($this->makePosition)('Driver', ['max_emp' => 1, 'exist_emp' => 1]);
    ($this->makePosition)('Cook', ['max_emp' => 1, 'exist_emp' => 2]);
    ($this->makePosition)('Retired', ['max_emp' => 5, 'exist_emp' => 0, 'is_active' => false]);

    $r = (new PositionVacanciesReport(($this->headOffice)()))->build();

    expect($r['positions'])->toBe(4)
        ->and($r['capacity'])->toBe(6)
        ->and($r['filled'])->toBe(4)
        ->and($r['vacantSeats'])->toBe(3)
        ->and($r['emptyPositions'])->toBe(1)
        ->and($r['overPositions'])->toBe(1)
        ->and($r['criticalVacant'])->toBe(1)
        ->and($r['list']->first()['title'])->toBe('Coordinator');

    $head = ($this->headOffice)();

    expect((new PositionVacanciesReport($head, ['state' => 'over']))->build()['list']->pluck('title')->all())->toBe(['Cook'])
        ->and((new PositionVacanciesReport($head, ['critical' => true]))->build()['list']->pluck('title')->all())->toBe(['Coordinator'])
        ->and((new PositionVacanciesReport($head, ['department_id' => '']))->build()['positions'])->toBe(4)
        ->and((new PositionVacanciesReport($head, ['department_id' => 999999]))->build()['positions'])->toBe(0);
});

test('the vacancies of a city HR stay inside their city', function () {
    ($this->makePosition)('Homs Officer', ['city_id' => $this->hms->id, 'max_emp' => 2]);

    $r = (new PositionVacanciesReport(($this->viewer)('HR-HMS', 'positions.view')))->build();

    expect($r['list']->pluck('title')->all())->toBe(['Homs Officer'])
        ->and($r['byCity']->pluck('label')->all())->toBe(['Homs']);
});

// Leave

test('leave shows what is owed, who is idle, days taken and waiting requests', function () {
    $a = ($this->person)();
    $b = ($this->person)();
    $balance = fn (Employee $employee, int $used): LeaveBalance => LeaveBalance::factory()->for(Contract::query()->where('employee_id', $employee->id)->firstOrFail())->create([
        'type_id' => LeaveTypeCases::Annual, 'deserved' => 160, 'used' => $used,
    ]);
    $balanceA = $balance($a, 0);
    $balanceB = $balance($b, 40);
    $perDay = app(LeaveRequestPricing::class)->hoursPerDayFor(Contract::query()->where('employee_id', $a->id)->firstOrFail()) ?: 8.0;

    $request = fn (LeaveBalance $of, array $attributes): LeaveRequest => LeaveRequest::factory()->for($of)->create($attributes + ['type' => 'day']);

    $request($balanceB, ['status' => LeaveRequestStatus::Accepted->value, 'start_at' => '2026-10-05', 'end_at' => '2026-10-05', 'computed_hours' => 40, 'approved_at' => '2026-10-06 10:00:00', 'created_at' => '2026-10-04 10:00:00']);
    $request($balanceB, ['status' => LeaveRequestStatus::Rejected->value, 'start_at' => '2026-10-20', 'end_at' => '2026-10-20', 'computed_hours' => 8, 'approved_at' => '2026-10-21 10:00:00', 'created_at' => '2026-10-19 10:00:00']);
    $request($balanceA, ['status' => LeaveRequestStatus::Pending->value, 'created_at' => now()->subDays(5)]);
    $request($balanceA, ['status' => LeaveRequestStatus::Pending->value, 'created_at' => now()->subDay()]);

    $r = (new LeaveReport(($this->headOffice)(), 365))->build();

    expect($r['people'])->toBe(2)
        ->and($r['owedDays'])->toBe(round(round(160 / $perDay, 1) + round(120 / $perDay, 1), 1))
        ->and($r['idleCount'])->toBe(1)
        ->and($r['takenDays'])->toBe(round(40 / $perDay, 1))
        ->and($r['pending'])->toBe(2)
        ->and($r['pendingOverThree'])->toBe(1)
        ->and($r['daysToAnswer'])->toBe(2.0)
        ->and($r['byDepartment']->pluck('label')->all())->toBe(['Programs'])
        ->and((new LeaveReport(($this->headOffice)(), 365, $this->department->id))->build()['people'])->toBe(2)
        ->and((new LeaveReport(($this->headOffice)(), 365, 999999))->build()['people'])->toBe(0);
});

// Training

test('training counts only the courses that count and measures coverage', function () {
    [$e1, $e2, $e3] = [($this->person)(), ($this->person)(), ($this->person)()];
    $first = CourseCategory::query()->create(['name_en' => 'Safety', 'name_ar' => 'السلامة']);
    $second = CourseCategory::query()->create(['name_en' => 'Finance', 'name_ar' => 'المالية']);

    $course = fn (Employee $employee, int $status, string $start, array $attributes = []): Course => Course::query()->create([
        'employee_id' => $employee->id, 'title' => 'Course', 'status' => $status, 'start_date' => $start,
    ] + $attributes);

    $course($e1, TrainingReport::APPROVED, '2026-03-01', ['cost' => 100, 'has_certificate' => true, 'category_id' => $first->id]);
    $course($e1, TrainingReport::FINISHED, '2026-05-01', ['cost' => 50, 'category_id' => $second->id]);
    $course($e2, TrainingReport::FINISHED, '2025-06-01');
    $course($e2, TrainingReport::REJECTED, '2026-04-01', ['cost' => 999]);
    $course($e3, TrainingReport::PENDING, '2026-04-01', ['cost' => 999]);

    $head = ($this->headOffice)();
    $r = (new TrainingReport($head, 2026))->build();

    expect($r['courses'])->toBe(2)
        ->and($r['trained'])->toBe(1)
        ->and($r['coveragePercent'])->toBe(33.3)
        ->and($r['cost'])->toBe(150.0)
        ->and($r['certificates'])->toBe(1)
        ->and($r['untrainedCount'])->toBe(2)
        ->and($r['byCategory']->pluck('count', 'label')->all())->toBe(['Safety' => 1, 'Finance' => 1])
        ->and($r['byStatus']->sum('count'))->toBe(4)
        ->and($r['byMonth']->all())->toBe(['2026-03' => 1, '2026-05' => 1]);

    $all = (new TrainingReport($head, null))->build();

    expect($all['courses'])->toBe(3)
        ->and($all['trained'])->toBe(2)
        ->and((new TrainingReport($head, 2026, $first->id))->build()['courses'])->toBe(1);
});

// Profile completeness

test('completeness checks presence only and lists the emptiest files first', function () {
    $full = ($this->person)([
        'national_number' => '01010101010', 'dob' => '1990-01-01', 'gender' => 1, 'nationality' => 1,
        'personal_phone' => '0999000000', 'personal_email' => 'full@example.test', 'address_en' => 'Aleppo',
    ]);
    DB::table('hr_media')->insert([
        'model_type' => 'App\Models\Employee', 'model_id' => $full->id, 'uuid' => (string) fake()->uuid(), 'collection_name' => 'avatar',
        'name' => 'a', 'file_name' => 'a.png', 'mime_type' => 'image/png', 'disk' => 'public', 'conversions_disk' => 'public',
        'size' => 10, 'manipulations' => '[]', 'custom_properties' => '[]', 'generated_conversions' => '[]', 'responsive_images' => '[]',
        'order_column' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]);
    Education::query()->create(['employee_id' => $full->id, 'name' => 'BSc', 'specialization' => 'IT']);
    Relative::query()->create(['employee_id' => $full->id, 'name' => 'Kin', 'phone' => '0999111111', 'relation' => 'Mother']);

    ($this->person)(['personal_phone' => '0999000001', 'gender' => 1]);
    $empty = ($this->person)(['gender' => null, 'nationality' => null]);

    $r = (new ProfileCompletenessReport(($this->headOffice)()))->build();

    expect($r['people'])->toBe(3)
        ->and($r['complete'])->toBe(1)
        ->and($r['list']->first()['username'])->toBe($empty->username)
        ->and($r['list'])->toHaveCount(2)
        ->and($r['byItem']->firstWhere('key', 'photo')['count'])->toBe(1)
        ->and($r['list']->first())->not->toHaveKey('personal_phone')
        ->and((new ProfileCompletenessReport(($this->headOffice)(), null, 'photo'))->build()['list'])->toHaveCount(2)
        ->and((new ProfileCompletenessReport(($this->headOffice)(), null, 'personal_phone'))->build()['list']->pluck('username')->all())->toBe([$empty->username])
        ->and((new ProfileCompletenessReport(($this->headOffice)(), $this->hms->id))->build()['people'])->toBe(0);
});

// Recruitment funnel

test('the funnel is empty and safe with no applications, and follows the applicant permission', function () {
    $head = ($this->headOffice)();
    $r = (new RecruitmentFunnelReport($head, 365))->build();

    expect($r['stages']->pluck('count')->all())->toBe([0, 0, 0, 0, 0])
        ->and($r['offerAcceptancePercent'])->toBeNull()
        ->and($r['requests']['submitted'])->toBe(0);

    Livewire::actingAs(($this->viewer)('HR-ALP', 'contracts.view'))->test('hr::reports.recruitment-funnel')->assertForbidden();
    Livewire::actingAs(($this->viewer)('HR-ALP', 'recruitment-applicants.view'))->test('hr::reports.recruitment-funnel')->assertOk()->assertSeeHtml('data-testid="funnel-requests"');
});
