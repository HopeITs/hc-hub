<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\Center;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Contracts\SaveContract;
use Modules\Hr\Actions\Contracts\TerminateContract;
use Modules\Hr\Enums\ContractPlace;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Enums\Currency;
use Modules\Hr\Enums\PayMode;
use Modules\Hr\Enums\SalaryPer;
use Modules\Hr\Enums\ScheduleMode;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Position;
use Spatie\Permission\Models\Permission;

/*
| Characterization tests for the contract critical path (roadmap 2.1, D13):
| saving a contract takes a seat on its position and activates the employee;
| terminating one records why and frees the seat. Each has its refusals.
*/

beforeEach(function () {
    $this->travelTo('2026-11-17 10:00:00');

    $this->annual = ContractType::query()->find(1) ?? ContractType::factory()->create(['id' => 1]);
    $this->position = Position::factory()->create(['max_emp' => 2, 'exist_emp' => 0]);
    $this->employee = Employee::factory()->create(['is_active' => false]);

    // HR with no city in its role name reaches every city and sees pay.
    $this->hr = User::factory()->role('HR-SYR')->create();
    $this->hr->givePermissionTo(...array_map(fn (string $name) => Permission::findOrCreate($name, 'web'), ['contracts.view', 'contracts.create', 'contracts.update']));
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
});

test('saving an active contract takes a seat on the position and activates the employee', function () {
    $contract = app(SaveContract::class)->handle(null, ($this->form)(), [], $this->hr);

    expect($contract->exists)->toBeTrue()
        ->and((int) $contract->employee_id)->toBe($this->employee->id)
        ->and((int) DB::table('positions')->where('id', $this->position->id)->value('exist_emp'))->toBe(1)
        ->and($this->employee->fresh()->is_active)->toBeTrue();
});

test('a contract term longer than a year is refused and nothing is saved', function () {
    expect(fn () => app(SaveContract::class)->handle(null, ($this->form)(['end_date' => '2027-12-31']), [], $this->hr))
        ->toThrow(ValidationException::class);

    expect(Contract::query()->count())->toBe(0)
        ->and((int) DB::table('positions')->where('id', $this->position->id)->value('exist_emp'))->toBe(0);
});

test('a first shift that ends before it starts is refused and nothing is saved', function () {
    expect(fn () => app(SaveContract::class)->handle(null, ($this->form)(['work_hours_from' => '09:00', 'work_hours_to' => '01:00']), [], $this->hr))
        ->toThrow(ValidationException::class);

    expect(Contract::query()->count())->toBe(0);
});

test('working days picked one by one replace the range, and never include the centers day off', function () {
    $center = Center::factory()->create(['weekends' => '5']);
    $this->position->update(['center_id' => $center->id]);

    // Friday is the center's day off: it cannot be a working day.
    expect(fn () => app(SaveContract::class)->handle(null, ($this->form)(['work_weekdays' => [5, 6, 0, 1, 3]]), [], $this->hr))
        ->toThrow(ValidationException::class);

    expect(Contract::query()->count())->toBe(0);

    $contract = app(SaveContract::class)->handle(null, ($this->form)(['work_day_from' => 0, 'work_day_to' => 4, 'work_weekdays' => ['6', '0', '1', '3', '4']]), [], $this->hr);

    expect($contract->fresh()->work_weekdays)->toBe('0,1,3,4,6')
        ->and($contract->fresh()->workWeekdays())->toBe([0, 1, 3, 4, 6])
        // One way to state the week or the other, never both.
        ->and($contract->fresh()->work_day_from)->toBeNull()
        ->and($contract->fresh()->work_day_to)->toBeNull();
});

test('a draft employee cannot be given a contract', function () {
    DB::table('employees')->where('id', $this->employee->id)->update(['record_status' => Employee::RECORD_DRAFT]);

    expect(fn () => app(SaveContract::class)->handle(null, ($this->form)(), [], $this->hr))
        ->toThrow(ValidationException::class);

    expect(Contract::query()->count())->toBe(0);
});

test('a login without contracts.create cannot save a contract', function () {
    $stranger = User::factory()->create();

    expect(fn () => app(SaveContract::class)->handle(null, ($this->form)(), [], $stranger))
        ->toThrow(AuthorizationException::class);

    expect(Contract::query()->count())->toBe(0);
});

test('terminating a contract records the date and reason, switches it off and frees its seat', function () {
    $contract = app(SaveContract::class)->handle(null, ($this->form)(), [], $this->hr);
    $reasonId = DB::table('termination_reasons')->insertGetId(['title_en' => 'Resigned', 'title_ar' => 'ط§ط³طھظ‚ط§ظ„ط©', 'created_at' => now(), 'updated_at' => now()]);

    app(TerminateContract::class)->handle($contract->fresh(), ['terminated_at' => '2026-11-15', 'termination_reason_id' => $reasonId], $this->hr);

    $row = DB::table('contracts')->find($contract->id);

    expect($row->terminated_at)->toStartWith('2026-11-15')
        ->and((int) $row->termination_reason_id)->toBe($reasonId)
        ->and((bool) $row->is_active)->toBeFalse()
        ->and((bool) $row->auto_renew)->toBeFalse()
        ->and((int) DB::table('positions')->where('id', $this->position->id)->value('exist_emp'))->toBe(0);
});

test('a termination dated before the contract started is refused and changes nothing', function () {
    $contract = app(SaveContract::class)->handle(null, ($this->form)(), [], $this->hr);
    $reasonId = DB::table('termination_reasons')->insertGetId(['title_en' => 'Resigned', 'title_ar' => 'ط§ط³طھظ‚ط§ظ„ط©', 'created_at' => now(), 'updated_at' => now()]);

    expect(fn () => app(TerminateContract::class)->handle($contract->fresh(), ['terminated_at' => '2026-10-01', 'termination_reason_id' => $reasonId], $this->hr))
        ->toThrow(ValidationException::class);

    expect(DB::table('contracts')->where('id', $contract->id)->value('terminated_at'))->toBeNull()
        ->and((int) DB::table('positions')->where('id', $this->position->id)->value('exist_emp'))->toBe(1);
});
