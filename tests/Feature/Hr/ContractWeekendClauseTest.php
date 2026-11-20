<?php

use Livewire\Livewire;
use Modules\Core\Models\Center;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Hr\Enums\ContractPlace;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Enums\Currency;
use Modules\Hr\Enums\PayMode;
use Modules\Hr\Enums\SalaryPer;
use Modules\Hr\Enums\ScheduleMode;
use Modules\Hr\Http\Controllers\Contracts\ContractLegalPrintController;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Contracts\WeekendClause;
use Spatie\Permission\Models\Permission;

/*
| Study Zone contracts may print "Friday is considered a day off…": a form
| checkbox (Study Zone positions only, off by default) and a printed sentence
| naming the weekend its centers in the city keep.
*/

beforeEach(function () {
    $this->travelTo('2026-11-17 10:00:00');

    $this->homs = City::factory()->create(['name_en' => 'Homs'])->id;
    // The printed pages recognise Study Zone by its id, as the HR app does.
    $this->studyZone = Program::query()->find(8) ?? Program::factory()->unassigned()->create(['id' => 8, 'name' => 'Study Zone', 'slug' => 'SZ']);
    $this->other = Program::factory()->unassigned()->create(['name' => 'Micro Project']);
    $this->annual = ContractType::query()->find(1) ?? ContractType::factory()->create(['id' => 1]);

    $center = Center::factory()->create(['city_id' => $this->homs, 'weekends' => '5']);
    $center->programs()->sync([$this->studyZone->id]);

    $this->position = fn (Program $program): Position => Position::factory()->create([
        'program_id' => $program->id, 'city_id' => $this->homs, 'center_id' => null, 'max_emp' => 5, 'exist_emp' => 0,
    ]);

    $this->contract = fn (Position $position, bool $clause): Contract => Contract::factory()
        ->for(Employee::factory()->create(['city_id' => $this->homs]))
        ->for($position)
        ->create([
            'contract_type_id' => 1, 'start_date' => '2026-11-01', 'end_date' => '2027-10-31',
            'weekly_hours' => 40, 'schedule_mode' => ScheduleMode::Fixed->value, 'show_weekend_clause' => $clause,
            'type' => ContractTypeEnum::FullTime->value, 'place' => ContractPlace::Onsite->value,
            'work_hours_from' => '08:00', 'work_hours_to' => '16:00',
            'pay_mode' => PayMode::Fixed->value, 'currency' => Currency::USD->value, 'salary' => 500, 'salary_per' => SalaryPer::Month->value,
        ]);

    $this->legalText = fn (Contract $contract): string => app(ContractLegalPrintController::class)($contract)->render();
});

test('a ticked Study Zone contract prints its centers weekend as the day off', function () {
    $html = ($this->legalText)(($this->contract)(($this->position)($this->studyZone), true));

    expect($html)->toContain('Friday is considered a day off, unless management deems it necessary')
        ->toContain('يعتبر يوم الجمعة يوم عطلة');
});

test('an unticked Study Zone contract prints no weekend sentence', function () {
    $html = ($this->legalText)(($this->contract)(($this->position)($this->studyZone), false));

    expect($html)->not->toContain('is considered a day off');
});

test('the clause names nothing for other programs', function () {
    $contract = ($this->contract)(($this->position)($this->other), true);

    expect(app(WeekendClause::class)->daysForContract($contract))->toBe([]);
});

test('two weekend days read as a pair in both languages', function () {
    expect(WeekendClause::sentences([5, 6]))->toBe([
        'en' => 'Friday and Saturday are considered days off, unless management deems it necessary, and are not considered overtime.',
        'ar' => 'يعتبر يوما الجمعة والسبت يومي عطلة، إلا في حال رأت الإدارة الحاجة لذلك، ولا يعتبر دوام إضافي.',
    ]);
});

test('the contract form offers the checkbox only for a Study Zone position and saves it', function () {
    $hr = User::factory()->role('HR-SYR')->create();
    $hr->givePermissionTo(...array_map(fn (string $name) => Permission::findOrCreate($name, 'web'), ['contracts.view', 'contracts.create', 'contracts.update']));

    $studyZonePosition = ($this->position)($this->studyZone);
    $otherPosition = ($this->position)($this->other);
    $contract = ($this->contract)($studyZonePosition, false);

    Livewire::actingAs($hr->fresh())->test('hr::contracts.form', ['contract' => $contract])
        ->assertSeeHtml('data-test="weekend-clause-option"')
        ->set('form.position_id', (string) $otherPosition->id)
        ->assertDontSeeHtml('data-test="weekend-clause-option"')
        ->set('form.position_id', (string) $studyZonePosition->id)
        ->set('form.show_weekend_clause', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($contract->fresh()->show_weekend_clause)->toBeTrue();
});
