<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Leave\DecideLeaveRequest;
use Modules\Hr\Actions\Leave\SaveLeaveRequest;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Enums\LeaveTypeCases;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Models\LeaveType;
use Modules\Hr\Models\Position;
use Spatie\Permission\Models\Permission;

/*
| Characterization tests for the leave critical path (roadmap 2.1, D13): an
| employee requests leave, and the holder of their position's direct
| manager decides it. "Today" is Wednesday 2026-10-21; 3 Nov 2026 is a Tuesday.
*/

beforeEach(function () {
    $this->travelTo('2026-10-21 10:00:00');
    Notification::fake();

    LeaveType::query()->find(LeaveTypeCases::Annual) ?? LeaveType::factory()->create(['id' => LeaveTypeCases::Annual]);
    ContractType::query()->find(1) ?? ContractType::factory()->create(['id' => 1]);

    $managerPosition = Position::factory()->create();
    $this->managerEmployee = Employee::factory()->create();
    Contract::factory()->for($this->managerEmployee)->create([
        'position_id' => $managerPosition->id, 'contract_type_id' => 1,
        'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
    ]);
    $this->manager = User::factory()->create(['employee_id' => $this->managerEmployee->id]);

    $this->employee = Employee::factory()->create();
    $contract = Contract::factory()->for($this->employee)->create([
        'position_id' => Position::factory()->create(['direct_manager_id' => $managerPosition->id])->id,
        'contract_type_id' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31',
        'weekly_hours' => 40, 'is_active' => true,
    ]);
    $this->balance = LeaveBalance::factory()->for($contract)->create(['type_id' => LeaveTypeCases::Annual, 'deserved' => 160]);

    $this->requester = User::factory()->create(['employee_id' => $this->employee->id]);
    $this->requester->givePermissionTo(Permission::findOrCreate('leaves.view-own', 'web'), Permission::findOrCreate('leaves.create', 'web'));
    $this->requester = $this->requester->fresh();

    $this->dayLeave = fn (string $date): array => [
        'type' => 'day',
        'leave_balance_id' => $this->balance->id,
        'start_at' => $date,
        'end_at' => $date,
        'reason' => 'Family visit out of town.',
    ];
});

test('an employee requests a day of leave and it waits as pending', function () {
    $leave = app(SaveLeaveRequest::class)->create($this->requester, null, ($this->dayLeave)('2026-11-03'));

    expect($leave->status)->toBe(LeaveRequestStatus::Pending->value)
        ->and($leave->start_at->toDateString())->toBe('2026-11-03')
        ->and((int) $leave->leave_balance_id)->toBe($this->balance->id);
});

test('a second request over a day already requested is refused', function () {
    app(SaveLeaveRequest::class)->create($this->requester, null, ($this->dayLeave)('2026-11-03'));

    expect(fn () => app(SaveLeaveRequest::class)->create($this->requester, null, ($this->dayLeave)('2026-11-03')))
        ->toThrow(ValidationException::class);

    expect(LeaveRequest::query()->count())->toBe(1);
});

test('a login without leaves.create cannot request leave', function () {
    $viewer = User::factory()->create(['employee_id' => Employee::factory()->create()->id]);

    expect(fn () => app(SaveLeaveRequest::class)->create($viewer->fresh(), null, ($this->dayLeave)('2026-11-03')))
        ->toThrow(AuthorizationException::class);
});

test('the direct manager accepts a request and its hours are taken from the balance', function () {
    $leave = app(SaveLeaveRequest::class)->create($this->requester, null, ($this->dayLeave)('2026-11-03'));
    $hours = (float) $leave->computed_hours;

    $decided = app(DecideLeaveRequest::class)->handle($this->manager, $leave->id, LeaveRequestStatus::Accepted->value, null);

    expect($decided->status)->toBe(LeaveRequestStatus::Accepted->value)
        ->and((int) $decided->approved_by)->toBe($this->managerEmployee->id)
        ->and($hours)->toBeGreaterThan(0)
        ->and((float) $this->balance->fresh()->used)->toBe($hours);
});

test('a rejection without a reason is refused and the request stays pending', function () {
    $leave = app(SaveLeaveRequest::class)->create($this->requester, null, ($this->dayLeave)('2026-11-03'));

    expect(fn () => app(DecideLeaveRequest::class)->handle($this->manager, $leave->id, LeaveRequestStatus::Rejected->value, ''))
        ->toThrow(ValidationException::class);

    expect($leave->fresh()->status)->toBe(LeaveRequestStatus::Pending->value);
});

test('someone who is not the manager cannot decide the request', function () {
    $leave = app(SaveLeaveRequest::class)->create($this->requester, null, ($this->dayLeave)('2026-11-03'));
    $stranger = User::factory()->create();

    expect(fn () => app(DecideLeaveRequest::class)->handle($stranger, $leave->id, LeaveRequestStatus::Accepted->value, null))
        ->toThrow(AuthorizationException::class);

    expect($leave->fresh()->status)->toBe(LeaveRequestStatus::Pending->value)
        ->and((float) $this->balance->fresh()->used)->toBe(0.0);
});

test('hr filing a daily leave for someone else may date it before today, an employee may not', function () {
    $hr = User::factory()->role('HR-SYR')->create();
    $hr->givePermissionTo(Permission::findOrCreate('leaves.create', 'web'));
    $hr = $hr->fresh();

    // "Today" is Wednesday 21 October: Monday the 19th is in the past.
    $leave = app(SaveLeaveRequest::class)->create($hr, $this->employee->id, ($this->dayLeave)('2026-10-19'));

    expect($leave->start_at->toDateString())->toBe('2026-10-19');

    expect(fn () => app(SaveLeaveRequest::class)->create($this->requester, null, ($this->dayLeave)('2026-10-19')))
        ->toThrow(ValidationException::class);

    expect(LeaveRequest::query()->count())->toBe(1);
});

test('a start date in the past says so in plain words', function () {
    try {
        app(SaveLeaveRequest::class)->create($this->requester, null, ($this->dayLeave)('2026-10-19'));
    } catch (ValidationException $invalid) {
        expect($invalid->errors()['start_at'][0])->toBe(__('hr_leave.validation.start-in-past'));

        return;
    }

    $this->fail('A leave dated in the past was accepted.');
});
