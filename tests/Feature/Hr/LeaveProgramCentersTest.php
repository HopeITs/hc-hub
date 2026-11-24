<?php

use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Center;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Hr\Enums\LeaveTypeCases;
use Modules\Hr\Enums\ScheduleMode;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveType;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Leave\LeaveQuote;
use Modules\Hr\Support\Leave\LeaveRequestPricing;
use Modules\Hr\Support\Leave\LeaveWindow;

/*
| A position without a center (every Study Zone position) takes the weekend
| and working hours from the centers linked to its program in its city
| (center_program): the most common weekend, the widest hours. A position
| with a center keeps its own center's, whatever else the center serves.
|
| "Today" is Wednesday 2026-10-21. 3 Nov 2026 is a Tuesday, 6 Nov a Friday,
| 7 Nov a Saturday.
*/

beforeEach(function () {
    $this->travelTo('2026-10-21 10:00:00');
    $this->pricing = new LeaveRequestPricing;

    $this->homs = City::factory()->create(['name_en' => 'Homs'])->id;
    $this->aleppo = City::factory()->create(['name_en' => 'Aleppo'])->id;
    $this->studyZone = Program::factory()->unassigned()->create(['name' => 'Study Zone'])->id;
    $this->micro = Program::factory()->unassigned()->create(['name' => 'Micro Project'])->id;
    $this->annual = LeaveType::query()->find(LeaveTypeCases::Annual)
        ?? LeaveType::factory()->create(['id' => LeaveTypeCases::Annual]);
    // Contract type 1 is the annual one: any other id may read as a service contract, which earns no leave.
    $this->annualContract = ContractType::query()->find(1) ?? ContractType::factory()->create(['id' => 1]);

    /** A center with its weekend, hours and the programs it serves. */
    $this->center = function (int $cityId, string $weekends, string $open = '09:00', string $close = '17:00', array $programs = [], bool $active = true): int {
        $center = Center::factory()->create([
            'city_id' => $cityId, 'weekends' => $weekends,
            'open_at' => $open.':00', 'close_at' => $close.':00', 'is_active' => $active,
        ]);
        $center->programs()->sync($programs);

        return $center->id;
    };

    /** An active volunteer on a fixed 40 h contract with a 160 h annual balance. */
    $this->volunteer = function (int $programId, int $cityId, ?int $center = null): array {
        $contract = Contract::factory()
            ->for(Employee::factory()->create(['city_id' => $cityId]))
            ->for(Position::factory()->create(['program_id' => $programId, 'city_id' => $cityId, 'center_id' => $center]))
            ->create([
                'contract_type_id' => 1,
                'start_date' => '2026-01-01', 'end_date' => '2026-12-31',
                'weekly_hours' => 40, 'schedule_mode' => ScheduleMode::Fixed->value, 'is_active' => true,
            ]);

        $balance = LeaveBalance::factory()->for($contract)->create(['type_id' => LeaveTypeCases::Annual, 'deserved' => 160]);

        return [Contract::query()->findOrFail($contract->id), LeaveBalance::query()->findOrFail($balance->id)];
    };
});

test('a volunteer without a center takes the linked centers hours and weekend', function () {
    ($this->center)($this->homs, '5', '08:00', '21:00', [$this->studyZone]);
    [$contract, $balance] = ($this->volunteer)($this->studyZone, $this->homs);

    $evening = $this->pricing->quote($contract, $balance, LeaveWindow::hours('2026-11-03', '17:00', '21:00'));
    $saturday = $this->pricing->quote($contract, $balance, LeaveWindow::days('2026-11-07', '2026-11-07'));
    $friday = $this->pricing->quote($contract, $balance, LeaveWindow::days('2026-11-06', '2026-11-06'));

    expect($this->pricing->weekendFor($contract))->toBe([[5], 'program'])
        ->and($evening->errors)->not->toContain(LeaveQuote::OUTSIDE_WORKING_HOURS)
        ->and($evening->requestedHours)->toBe(4.0)
        ->and($saturday->countedDays)->toBe(1)
        ->and($friday->errors)->toContain(LeaveQuote::NO_COUNTABLE_DAYS)
        // Six working days: 40 h a week is 6.67 h a day, not 8.
        ->and(round($evening->hoursPerDay, 4))->toBe(round(40 / 6, 4));
});

test('the linked centers hours bound the request exactly', function () {
    ($this->center)($this->homs, '5', '08:00', '21:00', [$this->studyZone]);
    [$contract, $balance] = ($this->volunteer)($this->studyZone, $this->homs);

    foreach ([['08:00', '09:00'], ['20:00', '21:00'], ['08:00', '21:00']] as [$from, $to]) {
        expect($this->pricing->quote($contract, $balance, LeaveWindow::hours('2026-11-03', $from, $to))->errors)
            ->not->toContain(LeaveQuote::OUTSIDE_WORKING_HOURS);
    }

    foreach ([['07:59', '09:00'], ['20:00', '21:01'], ['21:00', '22:00']] as [$from, $to]) {
        expect($this->pricing->quote($contract, $balance, LeaveWindow::hours('2026-11-03', $from, $to))->errors)
            ->toContain(LeaveQuote::OUTSIDE_WORKING_HOURS);
    }
});

test('several centers give the widest hours and the most common weekend', function () {
    ($this->center)($this->homs, '5', '09:00', '17:00', [$this->studyZone]);
    ($this->center)($this->homs, '5', '13:00', '21:00', [$this->studyZone]);
    // A six-day weekend is a data error and is not counted.
    ($this->center)($this->homs, '6,4,3,2,1,0', '09:00', '21:00', [$this->studyZone]);
    [$contract, $balance] = ($this->volunteer)($this->studyZone, $this->homs);

    expect($this->pricing->weekendFor($contract))->toBe([[5], 'program'])
        ->and($this->pricing->quote($contract, $balance, LeaveWindow::hours('2026-11-03', '09:00', '21:00'))->errors)
        ->not->toContain(LeaveQuote::OUTSIDE_WORKING_HOURS);
});

test('a tie keeps only the days every weekend shares', function () {
    ($this->center)($this->homs, '5,6', '09:00', '17:00', [$this->studyZone]);
    ($this->center)($this->homs, '5', '09:00', '17:00', [$this->studyZone]);
    [$contract] = ($this->volunteer)($this->studyZone, $this->homs);

    expect($this->pricing->weekendFor($contract))->toBe([[5], 'program']);
});

test('without a linked center in the city nothing changes', function () {
    // Linked, but in another city; and in this city, a center of another program.
    ($this->center)($this->aleppo, '5', '08:00', '21:00', [$this->studyZone]);
    ($this->center)($this->homs, '5', '08:00', '21:00', [$this->micro]);
    [$contract, $balance] = ($this->volunteer)($this->studyZone, $this->homs);

    $quote = $this->pricing->quote($contract, $balance, LeaveWindow::hours('2026-11-03', '17:00', '21:00'));

    expect($this->pricing->weekendFor($contract))->toBe([[0, 6], 'default'])
        ->and($quote->errors)->toContain(LeaveQuote::OUTSIDE_WORKING_HOURS);
});

test('the contracts own working days leave the volunteers day off uncounted', function () {
    // The center closes on Friday and the volunteer works five days, so a second day off (Tuesday) is theirs:
    // a leave from Monday to Wednesday is two days, not three, and Tuesday is listed as their own day off.
    ($this->center)($this->homs, '5', '08:00', '21:00', [$this->studyZone]);
    [$contract, $balance] = ($this->volunteer)($this->studyZone, $this->homs);
    $contract->update(['work_weekdays' => '6,0,1,3,4']);
    $contract = $contract->fresh();

    $quote = $this->pricing->quote($contract, $balance, LeaveWindow::days('2026-11-02', '2026-11-04'));

    expect($this->pricing->weekendFor($contract))->toBe([[2, 5], 'contract'])
        ->and($quote->countedDays)->toBe(2)
        ->and($quote->excludedDates)->toBe([['date' => '2026-11-03', 'reason' => 'day_off']])
        // Five working days: 40 h a week is 8 h a day.
        ->and($quote->requestedHours)->toBe(16.0);
});

test('a leave across the centers day and the volunteers own lists both', function () {
    ($this->center)($this->homs, '5', '09:00', '17:00', [$this->studyZone]);
    [$contract, $balance] = ($this->volunteer)($this->studyZone, $this->homs);
    $contract->update(['work_weekdays' => '0,1,2,3,4']);

    // Thursday 5 Nov to Sunday 8 Nov: Friday is the center's, Saturday is the volunteer's own.
    $quote = $this->pricing->quote($contract->fresh(), $balance, LeaveWindow::days('2026-11-05', '2026-11-08'));

    expect($quote->countedDays)->toBe(2)
        ->and($quote->excludedDates)->toBe([
            ['date' => '2026-11-06', 'reason' => 'weekend'],
            ['date' => '2026-11-07', 'reason' => 'day_off'],
        ]);
});

test('the place weekend ignores the days the contract picked', function () {
    ($this->center)($this->homs, '5', '09:00', '17:00', [$this->studyZone]);
    [$contract] = ($this->volunteer)($this->studyZone, $this->homs);
    $contract->update(['work_weekdays' => '6,0,1,3,4']);

    expect($this->pricing->placeWeekendFor($contract->fresh()))->toBe([[5], 'program'])
        ->and(Contract::parseWeekdays('4, 0,x,9,0'))->toBe([0, 4])
        ->and($contract->fresh()->workWeekdays())->toBe([0, 1, 3, 4, 6]);
});

test('an inactive linked center is ignored', function () {
    ($this->center)($this->homs, '5', '08:00', '21:00', [$this->studyZone], false);
    [$contract] = ($this->volunteer)($this->studyZone, $this->homs);

    expect($this->pricing->weekendFor($contract))->toBe([[0, 6], 'default']);
});

test('a volunteer with a center keeps their own centers schedule', function () {
    // A Hope Center serving two programs; the volunteer's position is at it.
    $hope = ($this->center)($this->homs, '0,6', '09:00', '17:00', [$this->micro, $this->studyZone]);
    ($this->center)($this->homs, '5', '08:00', '21:00', [$this->micro]);
    [$contract, $balance] = ($this->volunteer)($this->micro, $this->homs, $hope);

    $quote = $this->pricing->quote($contract, $balance, LeaveWindow::hours('2026-11-03', '17:00', '21:00'));

    expect($this->pricing->weekendFor($contract))->toBe([[0, 6], 'center'])
        ->and($quote->errors)->toContain(LeaveQuote::OUTSIDE_WORKING_HOURS);
});

test('study and science zone centers are linked to study zone by the data migration, once', function () {
    DB::table('programs')->where('id', $this->studyZone)->update(['slug' => 'SZ']);
    $center = fn (string $en, string $ar, bool $active = true): int => Center::factory()->create(['name_en' => $en, 'name_ar' => $ar, 'city_id' => $this->homs, 'is_active' => $active])->id;

    $armenian = $center('Study Zone Armenian', 'ستادي زون الأرمن');
    $science = $center('Science Zone', 'ستادي زون ساينس حمص');
    $closed = $center('Study Zone Al-Nouzha', 'ستادي زون النزهة', false);
    $arabicOnly = $center('SZ Inshaat', 'ستادي زون الانشائات');
    $hope = $center('Christian Hope Center', 'مركز الرجاء');

    // Set by hand before the migration, and a link to another program.
    DB::table('center_program')->insert([
        ['center_id' => $armenian, 'program_id' => $this->studyZone],
        ['center_id' => $hope, 'program_id' => $this->micro],
    ]);

    $migration = fn () => require base_path('modules/Hr/database/migrations/2026_11_13_000002_link_study_zone_centers_to_study_zone.php');
    $migration()->up();
    $migration()->up();

    expect(DB::table('center_program')->where('program_id', $this->studyZone)->orderBy('center_id')->pluck('center_id')->all())
        ->toBe([$armenian, $science, $closed, $arabicOnly])
        ->and(DB::table('center_program')->count())->toBe(5);
});

test('the data migration does nothing without the study zone program', function () {
    DB::table('programs')->where('id', $this->studyZone)->update(['name' => 'Tutoring', 'slug' => 'TUT']);
    Center::factory()->create(['name_en' => 'Study Zone Armenian', 'city_id' => $this->homs]);

    (require base_path('modules/Hr/database/migrations/2026_11_13_000002_link_study_zone_centers_to_study_zone.php'))->up();

    expect(DB::table('center_program')->count())->toBe(0);
});
