<?php

use App\Notifications\AppNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Models\BirthdayHolidayChoice;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Holiday;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Leave\BirthdayHoliday;
use Modules\Hr\Support\Leave\TeamCoverage;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

/*
| The birthday holiday tells the people it concerns: the volunteer when the
| birthday falls on a weekend or holiday (once), the direct manager and city
| HR when a day is asked for, the volunteer again when it is decided. The day
| off also shows on the team calendar. "Today" is Wednesday 2026-10-21; the
| weekend is Saturday and Sunday, so 24 Oct is a conflict and 22 Oct is not.
*/

beforeEach(function () {
    $this->travelTo('2026-10-21 10:00:00');

    ContractType::query()->find(1) ?? ContractType::factory()->create(['id' => 1]);

    $this->city = City::factory()->create(['slug' => 'HMS']);
    $this->contractFields = ['contract_type_id' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'weekly_hours' => 40, 'is_active' => true];

    $managerEmployee = Employee::factory()->create(['city_id' => $this->city->id]);
    $managerPosition = Position::factory()->create(['city_id' => $this->city->id]);
    Contract::factory()->for($managerEmployee)->for($managerPosition)->create($this->contractFields);
    $this->manager = User::factory()->create(['employee_id' => $managerEmployee->id])->fresh();
    $this->managerPosition = $managerPosition;

    $this->holiday = Holiday::query()->create([
        'name_en' => 'Birthday', 'name_ar' => 'عيد الميلاد',
        'start_date' => '2026-01-01', 'end_date' => '2026-12-31',
        'scope' => Holiday::SCOPE_NATIONAL, 'kind' => Holiday::KIND_BIRTHDAY,
    ]);

    $this->volunteer = function (string $dob, ?Position $position = null, int $contractTypeId = 1): array {
        $employee = Employee::factory()->create(['city_id' => $this->city->id, 'dob' => $dob, 'actual_dob' => $dob]);
        $position ??= Position::factory()->create(['city_id' => $this->city->id, 'direct_manager_id' => $this->managerPosition->id]);
        $contract = Contract::factory()->for($employee)->for($position)->create(['contract_type_id' => $contractTypeId] + $this->contractFields);

        return [$contract, User::factory()->create(['employee_id' => $employee->id])->fresh()];
    };

    $this->sentKeys = fn (User $user): array => Notification::sent($user, AppNotification::class)
        ->map(fn (AppNotification $notification): string => $notification->payload->key)
        ->filter(fn (string $key): bool => str_starts_with($key, 'hr_leave.'))
        ->values()
        ->all();
});

test('a volunteer whose birthday falls on a weekend is asked once to pick a day', function () {
    Notification::fake();
    [, $user] = ($this->volunteer)('1995-10-24');

    $this->artisan('birthday:notify-conflicts')->assertSuccessful();
    $this->artisan('birthday:notify-conflicts')->assertSuccessful();
    $this->travelTo('2026-10-22 10:00:00');
    $this->artisan('birthday:notify-conflicts')->assertSuccessful();

    expect(($this->sentKeys)($user))->toBe(['hr_leave.notifications.birthday_needed']);
});

test('no reminder when no day is needed, one is already chosen, it is too far or it is a service agreement', function () {
    Notification::fake();
    [, $workingDay] = ($this->volunteer)('1995-10-22');
    [, $tooFar] = ($this->volunteer)('1995-11-14');
    [$contract, $chose] = ($this->volunteer)('1995-10-25');
    app(BirthdayHoliday::class)->choose($contract, $this->holiday, 2026, CarbonImmutable::parse('2026-10-23'));
    ContractType::query()->find(5) ?? ContractType::factory()->create(['id' => 5]);
    [, $agreement] = ($this->volunteer)('1995-10-24', null, 5);

    $this->artisan('birthday:notify-conflicts')->assertSuccessful();

    foreach ([$workingDay, $tooFar, $chose, $agreement] as $user) {
        expect(($this->sentKeys)($user))->not->toContain('hr_leave.notifications.birthday_needed');
    }
});

test('the reminder horizon comes from config', function () {
    Notification::fake();
    config(['birthday-holiday.notify_days_ahead' => 30]);
    [, $user] = ($this->volunteer)('1995-11-14');

    $this->artisan('birthday:notify-conflicts')->assertSuccessful();

    expect(($this->sentKeys)($user))->toBe(['hr_leave.notifications.birthday_needed']);
});

test('a choice goes to the direct manager to decide and to city HR for information', function () {
    Notification::fake();
    [$contract, $user] = ($this->volunteer)('1995-10-24');

    $cityHr = User::factory()->create(['employee_id' => Employee::factory()->create(['city_id' => $this->city->id])->id]);
    $cityHr->assignRole(Role::findOrCreate('HR-HMS', 'web'));
    $otherCity = City::factory()->create(['slug' => 'ALP']);
    $otherHr = User::factory()->create(['employee_id' => Employee::factory()->create(['city_id' => $otherCity->id])->id]);
    $otherHr->assignRole(Role::findOrCreate('HR-ALP', 'web'));

    app(BirthdayHoliday::class)->choose($contract, $this->holiday, 2026, CarbonImmutable::parse('2026-10-23'));

    expect(($this->sentKeys)($this->manager))->toBe(['hr_leave.notifications.birthday_submitted'])
        ->and(($this->sentKeys)($cityHr))->toBe(['hr_leave.notifications.birthday_submitted_hr'])
        ->and(($this->sentKeys)($otherHr))->toBe([])
        ->and(($this->sentKeys)($user))->toBe([]);
});

test('city HR falls back to HR-SYR when the city has none', function () {
    Notification::fake();
    [$contract] = ($this->volunteer)('1995-10-24');
    $national = User::factory()->create(['employee_id' => Employee::factory()->create(['city_id' => $this->city->id])->id]);
    $national->assignRole(Role::findOrCreate('HR-SYR', 'web'));

    app(BirthdayHoliday::class)->choose($contract, $this->holiday, 2026, CarbonImmutable::parse('2026-10-23'));

    expect(($this->sentKeys)($national))->toBe(['hr_leave.notifications.birthday_submitted_hr']);
});

test('the volunteer is told the decision, and a refused decision tells nobody', function () {
    Notification::fake();
    [$contract, $user] = ($this->volunteer)('1995-10-24');
    $choice = app(BirthdayHoliday::class)->choose($contract, $this->holiday, 2026, CarbonImmutable::parse('2026-10-23'));
    [, $stranger] = ($this->volunteer)('1980-02-02');

    expect(fn () => app(BirthdayHoliday::class)->decide($stranger, $choice, true))->toThrow(HttpException::class);
    expect(($this->sentKeys)($user))->toBe([]);

    app(BirthdayHoliday::class)->decide($this->manager, $choice, false);

    expect(($this->sentKeys)($user))->toBe(['hr_leave.notifications.birthday_rejected'])
        ->and(BirthdayHolidayChoice::query()->sole()->status)->toBe(BirthdayHolidayChoice::REJECTED);
});

test('the birthday holiday shows as leave to the manager and neutrally to colleagues', function () {
    [, $user] = ($this->volunteer)('1995-10-22');
    [, $colleague] = ($this->volunteer)('1990-03-03');
    $coverage = app(TeamCoverage::class);
    $day = CarbonImmutable::parse('2026-10-22');

    $managerView = $coverage->absences($this->manager, 'reports', $day, $day)->sole();

    expect($managerView['type'])->toBe(__('hr_leave.calendar.birthday_holiday'))
        ->and($managerView['status'])->toBe('accepted')
        ->and($managerView['counted'])->toBe(['2026-10-22'])
        ->and($coverage->coverage($this->manager, 'reports', $day, $day)['2026-10-22']['away'])->toBe(1);

    $colleagueView = $coverage->absences($colleague, 'colleagues', $day, $day)->sole();

    expect($colleagueView['type'])->toBe(__('hr_leave.calendar.on_leave'))
        ->and($colleagueView['status'])->toBeNull()
        ->and($colleagueView['id'])->toBeNull();

    $own = $coverage->absences($user, 'colleagues', $day, $day)->firstWhere('is_self', true);

    expect($own['type'])->toBe(__('hr_leave.calendar.birthday_holiday'));
});

test('a moved day shows only once approved, and a birthday is not a holiday for the rest of the team', function () {
    [$contract, $user] = ($this->volunteer)('1995-10-24');
    ($this->volunteer)('1990-03-03');
    [$from, $to] = [CarbonImmutable::parse('2026-10-19'), CarbonImmutable::parse('2026-10-25')];
    $coverage = app(TeamCoverage::class);

    $choice = app(BirthdayHoliday::class)->choose($contract, $this->holiday, 2026, CarbonImmutable::parse('2026-10-23'));
    expect($coverage->absences($this->manager, 'reports', $from, $to))->toHaveCount(0);

    app(BirthdayHoliday::class)->decide($this->manager, $choice, true);

    expect($coverage->absences($this->manager, 'reports', $from, CarbonImmutable::parse('2026-10-25'))->sole()['dates'])->toBe(['2026-10-23']);

    $grid = $coverage->coverage($user, 'colleagues', CarbonImmutable::parse('2026-10-23'), CarbonImmutable::parse('2026-10-23'))['2026-10-23'];

    expect($grid['is_holiday'])->toBeFalse()->and($grid['away'])->toBe(1);
});
