<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Core\Models\Center;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Contracts\SaveContract;
use Modules\Hr\Actions\Contracts\TerminateContract;
use Modules\Hr\Enums\ContractAmendmentStatus;
use Modules\Hr\Enums\ContractPlace;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Enums\Currency;
use Modules\Hr\Enums\LeaveTypeCases;
use Modules\Hr\Enums\PayMode;
use Modules\Hr\Enums\SalaryPer;
use Modules\Hr\Enums\ScheduleMode;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractAmendment;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveType;
use Modules\Hr\Models\Position;
use Spatie\Permission\Models\Permission;

/*
| Contract scenarios brought to parity with hc-hr (roadmap 7.10b): leave
| balances follow the contract, a reserved draft is upgraded in place, applied
| amendments freeze the amendable terms, ended contracts drop amendments that
| can no longer start, duplicate submits save once, the weekday schedule, and
| the renew modal.
*/

beforeEach(function () {
    $this->travelTo('2026-11-17 10:00:00');

    $this->annual = ContractType::query()->find(1) ?? ContractType::factory()->create(['id' => 1]);
    $this->position = Position::factory()->create(['max_emp' => 2, 'exist_emp' => 0]);
    $this->employee = Employee::factory()->create(['is_active' => false]);

    DB::table('annual_leave_rules')->insert(['days' => 14, 'day_per_month' => 1.17, 'years_of_volunteering' => 0, 'created_at' => now(), 'updated_at' => now()]);

    foreach (range(LeaveTypeCases::Annual, LeaveTypeCases::Unpaid) as $typeId) {
        LeaveType::factory()->create(['id' => $typeId, 'base_days' => 0]);
    }

    $this->hr = User::factory()->role('HR-SYR')->create();
    $this->hr->givePermissionTo(...array_map(fn (string $name) => Permission::findOrCreate($name, 'web'), ['contracts.view', 'contracts.create', 'contracts.update', 'contracts.renew']));
    $this->hr = $this->hr->fresh();

    $this->form = fn (array $overrides = []): array => $overrides + [
        'employee_id' => $this->employee->id,
        'position_id' => $this->position->id,
        'contract_type_id' => $this->annual->id,
        'type' => ContractTypeEnum::FullTime->value,
        'place' => ContractPlace::Onsite->value,
        'is_active' => true,
        'start_date' => '2026-11-01',
        'end_date' => '2027-10-31',
        'weekly_hours' => 40,
        'schedule_mode' => ScheduleMode::Fixed->value,
        'work_hours_from' => '08:00',
        'work_hours_to' => '16:00',
        'pay_mode' => PayMode::Fixed->value,
        'currency' => Currency::USD->value,
        'salary' => '500',
        'salary_per' => SalaryPer::Month->value,
    ];

    $this->annualDeserved = fn (int $contractId): ?int => DB::table('leave_balances')
        ->where('contract_id', $contractId)
        ->where('type_id', LeaveTypeCases::Annual)
        ->value('deserved');
});

test('saving a new contract creates its leave balances', function () {
    $contract = app(SaveContract::class)->handle(null, ($this->form)(), [], $this->hr);

    expect(($this->annualDeserved)($contract->id))->toBeGreaterThan(0)
        ->and(DB::table('leave_balances')->where('contract_id', $contract->id)->count())->toBeGreaterThan(1);
});

test('editing the weekly hours re-prices the leave balances', function () {
    $contract = app(SaveContract::class)->handle(null, ($this->form)(), [], $this->hr);
    $before = ($this->annualDeserved)($contract->id);

    app(SaveContract::class)->handle($contract->fresh(), ($this->form)(['weekly_hours' => 20]), [], $this->hr);

    expect(($this->annualDeserved)($contract->id))->toBe(intdiv($before, 2))
        ->and(DB::table('leave_balances')->where('contract_id', $contract->id)->where('type_id', LeaveTypeCases::Annual)->count())->toBe(1);
});

test('a manual renewal writes the new contract its own leave balances', function () {
    $source = app(SaveContract::class)->handle(null, ($this->form)(), [], $this->hr);

    $renewal = app(SaveContract::class)->handle(null, ($this->form)(['start_date' => '2027-11-01', 'end_date' => '2028-10-31']), [], $this->hr, $source->fresh());

    expect($renewal->id)->not->toBe($source->id)
        ->and(($this->annualDeserved)($renewal->id))->toBeGreaterThan(0);
});

test('a contract for an employee with a reserved draft upgrades the draft instead of adding a row', function () {
    $draft = Contract::query()->create([
        'employee_id' => $this->employee->id,
        'position_id' => $this->position->id,
        'contract_type_id' => $this->annual->id,
        'type' => ContractTypeEnum::Draft->value,
        'is_active' => true,
    ]);

    $contract = app(SaveContract::class)->handle(null, ($this->form)(), [], $this->hr);

    expect($contract->id)->toBe($draft->id)
        ->and(DB::table('contracts')->where('employee_id', $this->employee->id)->count())->toBe(1)
        ->and((int) DB::table('contracts')->where('id', $draft->id)->value('type'))->toBe(ContractTypeEnum::FullTime->value)
        ->and((int) DB::table('positions')->where('id', $this->position->id)->value('exist_emp'))->toBe(1);
});

test('an edit keeps the schedule and pay an applied amendment set', function () {
    $contract = app(SaveContract::class)->handle(null, ($this->form)(), [], $this->hr);
    ContractAmendment::factory()->create([
        'contract_id' => $contract->id,
        'status' => ContractAmendmentStatus::Applied->value,
        'effective_from' => '2026-11-10',
    ]);

    app(SaveContract::class)->handle($contract->fresh(), ($this->form)(['weekly_hours' => 20, 'salary' => '900', 'work_hours_to' => '12:00', 'end_date' => '2027-09-30']), [], $this->hr);

    $row = DB::table('contracts')->find($contract->id);

    expect((int) $row->weekly_hours)->toBe(40)
        ->and($contract->fresh()->salary)->toBe('500.00')
        ->and(substr((string) $row->work_hours_to, 0, 5))->toBe('16:00')
        ->and((string) $row->end_date)->toStartWith('2027-09-30');
});

test('the form locks the amendable sections once an amendment is applied', function () {
    $contract = app(SaveContract::class)->handle(null, ($this->form)(), [], $this->hr);
    ContractAmendment::factory()->create(['contract_id' => $contract->id, 'status' => ContractAmendmentStatus::Applied->value]);

    Livewire::actingAs($this->hr)->test('hr::contracts.form', ['contract' => $contract->fresh()])
        ->assertSet('termsLocked', true)
        ->assertSeeHtml('data-test="terms-locked"');
});

test('moving the end date before an approved amendment cancels that amendment', function () {
    $contract = app(SaveContract::class)->handle(null, ($this->form)(), [], $this->hr);
    $amendment = ContractAmendment::factory()->approved()->create(['contract_id' => $contract->id, 'effective_from' => '2027-06-01']);

    app(SaveContract::class)->handle($contract->fresh(), ($this->form)(['end_date' => '2027-03-31']), [], $this->hr);

    expect(DB::table('contract_amendments')->where('id', $amendment->id)->exists())->toBeFalse();
});

test('terminating a contract cancels the approved amendments it ended before', function () {
    $contract = app(SaveContract::class)->handle(null, ($this->form)(), [], $this->hr);
    $amendment = ContractAmendment::factory()->approved()->create(['contract_id' => $contract->id, 'effective_from' => '2027-01-01']);
    $reasonId = DB::table('termination_reasons')->insertGetId(['title_en' => 'Resigned', 'title_ar' => 'ط§ط³طھظ‚ط§ظ„ط©', 'created_at' => now(), 'updated_at' => now()]);

    app(TerminateContract::class)->handle($contract->fresh(), ['terminated_at' => '2026-11-15', 'termination_reason_id' => $reasonId], $this->hr);

    expect(DB::table('contract_amendments')->where('id', $amendment->id)->exists())->toBeFalse();
});

test('submitting the contract form twice saves one contract', function () {
    $page = Livewire::actingAs($this->hr)->test('hr::contracts.form');
    $page->set('form', array_merge($page->get('form'), ($this->form)()));

    $page->call('save')->assertHasNoErrors();
    $page->call('save')->assertHasNoErrors();

    expect(DB::table('contracts')->where('employee_id', $this->employee->id)->count())->toBe(1);
});

test('the picked work days and the weekend clause are stored', function () {
    $contract = app(SaveContract::class)->handle(null, ($this->form)(['work_weekdays' => [2, 0, 1], 'show_weekend_clause' => true]), [], $this->hr);

    $row = DB::table('contracts')->find($contract->id);

    expect($row->work_weekdays)->toBe('0,1,2')
        ->and((bool) $row->show_weekend_clause)->toBeTrue()
        ->and($contract->fresh()->workWeekdays())->toBe([0, 1, 2]);
});

test('the centre day off cannot be picked as a work day', function () {
    $center = Center::factory()->create(['weekends' => '5']);
    $this->position->update(['center_id' => $center->id]);

    app(SaveContract::class)->handle(null, ($this->form)(['work_weekdays' => [4, 5]]), [], $this->hr);
})->throws(ValidationException::class);

test('a fixed schedule saves without work hours', function () {
    $contract = app(SaveContract::class)->handle(null, ($this->form)(['work_hours_from' => '', 'work_hours_to' => '']), [], $this->hr);

    expect(DB::table('contracts')->where('id', $contract->id)->value('work_hours_from'))->toBeNull();
});

test('the form works out the weekly hours from the days and hours', function () {
    Livewire::actingAs($this->hr)->test('hr::contracts.form')
        ->set('form.work_hours_from', '08:00')
        ->set('form.work_hours_to', '14:00')
        ->set('form.work_weekdays', ['0', '1', '2', '3', '4'])
        ->assertSet('form.weekly_hours', '30');
});

test('renew as is writes the next contract and its leave balances once', function () {
    $source = app(SaveContract::class)->handle(null, ($this->form)(), [], $this->hr);

    $page = Livewire::actingAs($this->hr)->test('hr::contracts.show', ['contract' => $source->fresh()])
        ->call('openRenew')
        ->assertSet('renewal.start_date', '2027-11-01')
        ->assertSet('renewal.end_date', '2028-10-31');

    $page->call('renewAsIs')->assertHasNoErrors();
    $page->call('renewAsIs');

    $renewals = Contract::query()->where('employee_id', $this->employee->id)->whereKeyNot($source->id)->get();

    expect($renewals)->toHaveCount(1)
        ->and($renewals->first()->start_date->toDateString())->toBe('2027-11-01')
        ->and(($this->annualDeserved)($renewals->first()->id))->toBeGreaterThan(0);
});

test('renew with edit opens the form on the chosen period under a renewing banner', function () {
    $source = app(SaveContract::class)->handle(null, ($this->form)(), [], $this->hr);

    Livewire::actingAs($this->hr)->test('hr::contracts.show', ['contract' => $source->fresh()])
        ->call('openRenew')
        ->set('renewal.start_date', '2027-12-01')
        ->set('renewal.end_date', '2028-06-30')
        ->call('renewWithEdit')
        ->assertRedirect(route('hr.contracts.renew', ['contract' => $source->id, 'start' => '2027-12-01', 'end' => '2028-06-30']));

    $this->withoutVite()->actingAs($this->hr)
        ->get(route('hr.contracts.renew', ['contract' => $source->id, 'start' => '2027-12-01', 'end' => '2028-06-30']))
        ->assertOk()
        ->assertSee('data-test="renewal-banner"', false)
        ->assertSee('2027-12-01')
        ->assertSee('2028-06-30');
});
