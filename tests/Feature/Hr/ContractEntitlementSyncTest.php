<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Contracts\ContractAmendmentService;
use Modules\Hr\Actions\Contracts\SaveContract;
use Modules\Hr\Actions\Contracts\TerminateContract;
use Modules\Hr\Enums\ContractPlace;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Enums\Currency;
use Modules\Hr\Enums\LeaveTypeCases;
use Modules\Hr\Enums\PayMode;
use Modules\Hr\Enums\SalaryPer;
use Modules\Hr\Enums\ScheduleMode;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\EmployeeEntitlement;
use Modules\Hr\Models\Entitlement;
use Modules\Hr\Models\LeaveType;
use Modules\Hr\Models\Position;
use Modules\Hr\Models\PositionEntitlement;
use Modules\Hr\Support\Entitlements\Entitlements;
use Modules\Hr\Support\Journeys\OffboardingJourneys;
use Spatie\Permission\Models\Permission;

/*
| The Permissions Library follows the contract through the real write paths
| (roadmap 7.11, hc-hr's ContractObserver): saving a contract records its
| position's defaults, a move marks the old ones for review, an amendment
| changes nothing, and ending the contract revokes nothing but opens the exit
| journey that lists what is still held.
*/

beforeEach(function () {
    $this->travelTo('2026-11-17 10:00:00');
    Notification::fake();

    $this->annual = ContractType::query()->find(1) ?? ContractType::factory()->create(['id' => 1]);
    $this->position = Position::factory()->create(['max_emp' => 5, 'exist_emp' => 0]);
    $this->otherPosition = Position::factory()->create(['city_id' => $this->position->city_id, 'max_emp' => 5, 'exist_emp' => 0]);
    $this->employee = Employee::factory()->create(['city_id' => $this->position->city_id, 'is_active' => false]);

    DB::table('annual_leave_rules')->insert(['days' => 14, 'day_per_month' => 1.17, 'years_of_volunteering' => 0, 'created_at' => now(), 'updated_at' => now()]);

    foreach (range(LeaveTypeCases::Annual, LeaveTypeCases::Unpaid) as $typeId) {
        LeaveType::factory()->create(['id' => $typeId, 'base_days' => 0]);
    }

    $this->hr = User::factory()->role('HR-SYR')->create();
    $this->hr->givePermissionTo(...array_map(fn (string $name) => Permission::findOrCreate($name, 'web'), [
        'contracts.view', 'contracts.create', 'contracts.update', 'employees.update', 'employees.view', 'entitlements.assign',
    ]));
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

    $this->save = fn (?Contract $contract = null, array $overrides = []): Contract => app(SaveContract::class)
        ->handle($contract?->fresh(), ($this->form)($overrides), [], $this->hr);

    $this->defaultOf = function (string $name, Position $position): Entitlement {
        $entitlement = Entitlement::factory()->create(['name_en' => $name, 'name_ar' => $name]);
        PositionEntitlement::query()->create(['entitlement_id' => $entitlement->id, 'position_id' => $position->id]);

        return $entitlement;
    };

    $this->rows = fn () => EmployeeEntitlement::query()->where('employee_id', $this->employee->id)->get()->keyBy('entitlement_id');
});

test('saving a new contract records its position defaults as required', function () {
    $item = ($this->defaultOf)('IT database', $this->position);
    ($this->defaultOf)('Elsewhere', $this->otherPosition);

    ($this->save)();

    $rows = ($this->rows)();

    expect($rows->keys()->all())->toBe([$item->id])
        ->and($rows[$item->id]->state)->toBe(EmployeeEntitlement::STATE_REQUIRED)
        ->and($rows[$item->id]->source)->toBe(EmployeeEntitlement::SOURCE_DEFAULT);
});

test('a contract saved inactive records nothing until it is switched on', function () {
    $item = ($this->defaultOf)('IT database', $this->position);

    $contract = ($this->save)(null, ['is_active' => false]);

    expect(($this->rows)())->toBeEmpty();

    ($this->save)($contract, ['is_active' => true]);

    expect(($this->rows)()->keys()->all())->toBe([$item->id]);
});

test('upgrading a reserved draft through the form records the defaults once', function () {
    $item = ($this->defaultOf)('IT database', $this->position);
    Contract::query()->create([
        'employee_id' => $this->employee->id,
        'position_id' => $this->position->id,
        'contract_type_id' => $this->annual->id,
        'type' => ContractTypeEnum::Draft->value,
        'is_active' => true,
    ]);

    expect(($this->rows)())->toBeEmpty();

    ($this->save)();

    expect(EmployeeEntitlement::query()->where('employee_id', $this->employee->id)->pluck('entitlement_id')->all())->toBe([$item->id]);
});

test('editing a contract onto another position adds its defaults and marks the old ones for review', function () {
    $old = ($this->defaultOf)('Officer database', $this->position);
    $new = ($this->defaultOf)('Coordinator group', $this->otherPosition);
    $contract = ($this->save)();
    app(Entitlements::class)->markGranted(($this->rows)()[$old->id], $this->hr);

    ($this->save)($contract, ['position_id' => $this->otherPosition->id]);

    $rows = ($this->rows)();

    expect($rows[$new->id]->state)->toBe(EmployeeEntitlement::STATE_REQUIRED)
        ->and($rows[$old->id]->state)->toBe(EmployeeEntitlement::STATE_GRANTED)
        ->and($rows[$old->id]->needs_review_at)->not->toBeNull();
});

test('an edit that keeps the seat changes nothing', function () {
    $item = ($this->defaultOf)('IT database', $this->position);
    $contract = ($this->save)();
    $row = ($this->rows)()[$item->id];
    app(Entitlements::class)->revoke($row, $this->hr);

    ($this->save)($contract, ['weekly_hours' => 30]);

    expect($row->fresh()->state)->toBe(EmployeeEntitlement::STATE_REVOKED)
        ->and(($this->rows)())->toHaveCount(1);
});

test('an applied amendment leaves the items as they were', function () {
    $item = ($this->defaultOf)('IT database', $this->position);
    $contract = ($this->save)();
    $row = ($this->rows)()[$item->id];
    app(Entitlements::class)->markGranted($row, $this->hr);
    ($this->defaultOf)('Added later', $this->position);

    $amendment = app(ContractAmendmentService::class)->draft($contract->fresh(), ['weekly_hours' => 30], today()->toDateString());
    app(ContractAmendmentService::class)->approve($amendment);

    expect((int) $contract->fresh()->weekly_hours)->toBe(30)
        ->and(($this->rows)()->keys()->all())->toBe([$item->id])
        ->and($row->fresh()->state)->toBe(EmployeeEntitlement::STATE_GRANTED)
        ->and($row->fresh()->needs_review_at)->toBeNull();
});

test('terminating revokes nothing and the exit journey lists the items still held', function () {
    $held = ($this->defaultOf)('IT database', $this->position);
    $open = ($this->defaultOf)('Team group', $this->position);
    $contract = ($this->save)();
    app(Entitlements::class)->markGranted(($this->rows)()[$held->id], $this->hr);
    $reasonId = DB::table('termination_reasons')->insertGetId(['title_en' => 'Resigned', 'title_ar' => 'Resigned', 'created_at' => now(), 'updated_at' => now()]);

    app(TerminateContract::class)->handle($contract->fresh(), ['terminated_at' => '2026-11-17', 'termination_reason_id' => $reasonId], $this->hr);

    $rows = ($this->rows)();
    $journeys = app(OffboardingJourneys::class);
    $steps = fn () => collect($journeys->steps($this->employee->fresh(), $journeys->latestFor($this->employee->id)))->keyBy('key');

    expect($rows[$held->id]->state)->toBe(EmployeeEntitlement::STATE_GRANTED)
        ->and($rows[$open->id]->state)->toBe(EmployeeEntitlement::STATE_REQUIRED)
        ->and($journeys->latestFor($this->employee->id))->not->toBeNull()
        ->and($steps()['entitlements']['state'])->toBe('todo')
        ->and($steps()['entitlements']['data']['names'])->toBe([$held->name]);

    app(Entitlements::class)->revoke($rows[$held->id], $this->hr);

    expect($steps()['entitlements']['state'])->toBe('done');
});

test('a contract switched back on after leaving brings a revoked default back as required', function () {
    $item = ($this->defaultOf)('IT database', $this->position);
    $contract = ($this->save)();
    $row = ($this->rows)()[$item->id];
    app(Entitlements::class)->revoke($row, $this->hr);

    ($this->save)($contract, ['is_active' => false]);

    expect($row->fresh()->state)->toBe(EmployeeEntitlement::STATE_REVOKED);

    ($this->save)($contract, ['is_active' => true]);

    expect($row->fresh()->state)->toBe(EmployeeEntitlement::STATE_REQUIRED)
        ->and(($this->rows)())->toHaveCount(1);
});
