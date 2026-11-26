<?php

use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Leave\DecideLeaveRequest;
use Modules\Hr\Actions\Leave\SaveLeaveRequest;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Enums\LeaveTypeCases;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Models\LeaveType;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Leave\LeaveAlternativeEmployees;
use Modules\Hr\Support\Leave\LeaveWindow;
use Spatie\Permission\Models\Permission;

/*
| Hub 7.10d (items 1 and 3): the decider may change the covering colleague
| when accepting (the requester is told), and the list of colleagues is
| narrowed to the period asked for. "Today" is Wednesday 2026-10-21; 3 Nov
| 2026 is a Tuesday.
*/

beforeEach(function () {
    $this->travelTo('2026-10-21 10:00:00');
    Notification::fake();

    LeaveType::query()->find(LeaveTypeCases::Annual) ?? LeaveType::factory()->create(['id' => LeaveTypeCases::Annual]);
    ContractType::query()->find(1) ?? ContractType::factory()->create(['id' => 1]);

    $city = City::factory()->create();
    $program = Program::factory()->unassigned()->create();
    $department = Department::factory()->create();
    $place = ['city_id' => $city->id, 'program_id' => $program->id, 'department_id' => $department->id];

    $managerPosition = Position::factory()->create($place);
    $managerEmployee = Employee::factory()->create(['city_id' => $city->id]);
    Contract::factory()->for($managerEmployee)->create(['position_id' => $managerPosition->id, 'contract_type_id' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]);
    $this->manager = User::factory()->create(['employee_id' => $managerEmployee->id]);

    $this->employee = Employee::factory()->create(['city_id' => $city->id]);
    $contract = Contract::factory()->for($this->employee)->create([
        'position_id' => Position::factory()->create($place + ['direct_manager_id' => $managerPosition->id])->id,
        'contract_type_id' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'weekly_hours' => 40, 'is_active' => true,
    ]);
    $this->balance = LeaveBalance::factory()->for($contract)->create(['type_id' => LeaveTypeCases::Annual, 'deserved' => 160]);
    $this->requester = User::factory()->create(['employee_id' => $this->employee->id]);
    $this->requester->givePermissionTo(Permission::findOrCreate('leaves.view-own', 'web'), Permission::findOrCreate('leaves.create', 'web'));
    $this->requester = $this->requester->fresh();

    $this->colleague = function () use ($city, $place): Employee {
        $colleague = Employee::factory()->create(['city_id' => $city->id]);
        Contract::factory()->for($colleague)->create([
            'position_id' => Position::factory()->create($place)->id,
            'contract_type_id' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);

        return $colleague;
    };
    $this->first = ($this->colleague)();
    $this->second = ($this->colleague)();

    $this->request = fn (?int $alternativeId, string $date = '2026-11-03'): LeaveRequest => app(SaveLeaveRequest::class)->create($this->requester, null, [
        'type' => 'day', 'leave_balance_id' => $this->balance->id, 'start_at' => $date, 'end_at' => $date,
        'reason' => 'Family visit out of town.', 'alternative_employee' => $alternativeId,
    ]);
    $this->heard = fn (string $key): bool => Notification::sent($this->requester, AppNotification::class)
        ->contains(fn (AppNotification $notification): bool => $notification->payload->key === 'hr_leave.notifications.'.$key);
});

test('the decider names another covering colleague when accepting and the requester is told', function () {
    $leave = ($this->request)($this->first->id);

    $decided = app(DecideLeaveRequest::class)->handle($this->manager, $leave->id, LeaveRequestStatus::Accepted->value, null, (string) $this->second->id);

    expect($decided->status)->toBe(LeaveRequestStatus::Accepted->value)
        ->and((int) $leave->fresh()->alternative_employee_id)->toBe($this->second->id)
        ->and(($this->heard)('alternative_changed'))->toBeTrue();
});

test('keeping the covering colleague sends no alternative notification', function () {
    $leave = ($this->request)($this->first->id);

    app(DecideLeaveRequest::class)->handle($this->manager, $leave->id, LeaveRequestStatus::Accepted->value, null, (string) $this->first->id);

    expect((int) $leave->fresh()->alternative_employee_id)->toBe($this->first->id)
        ->and(($this->heard)('alternative_changed'))->toBeFalse();
});

test('a decision that leaves the alternative out keeps it, and clearing it is told too', function () {
    $kept = ($this->request)($this->first->id);
    app(DecideLeaveRequest::class)->handle($this->manager, $kept->id, LeaveRequestStatus::Accepted->value, null);
    expect((int) $kept->fresh()->alternative_employee_id)->toBe($this->first->id);

    $cleared = ($this->request)($this->first->id, '2026-11-10');
    app(DecideLeaveRequest::class)->handle($this->manager, $cleared->id, LeaveRequestStatus::Accepted->value, null, '');

    expect($cleared->fresh()->alternative_employee_id)->toBeNull()
        ->and(($this->heard)('alternative_changed'))->toBeTrue();
});

test('a colleague outside the list cannot be named by the decider, and nothing is decided', function () {
    $leave = ($this->request)($this->first->id);
    $stranger = Employee::factory()->create();

    expect(fn () => app(DecideLeaveRequest::class)->handle($this->manager, $leave->id, LeaveRequestStatus::Accepted->value, null, (string) $stranger->id))
        ->toThrow(ValidationException::class);

    expect($leave->fresh()->status)->toBe(LeaveRequestStatus::Pending->value)
        ->and((float) $this->balance->fresh()->used)->toBe(0.0);
});

test('a rejection never changes the alternative', function () {
    $leave = ($this->request)($this->first->id);

    app(DecideLeaveRequest::class)->handle($this->manager, $leave->id, LeaveRequestStatus::Rejected->value, 'Not now.', (string) $this->second->id);

    expect((int) $leave->fresh()->alternative_employee_id)->toBe($this->first->id)
        ->and(($this->heard)('alternative_changed'))->toBeFalse();
});

test('the list of covering colleagues is narrowed to the period asked for', function () {
    $this->first->contracts()->first()->leaveBalances()->create(['type_id' => LeaveTypeCases::Annual, 'deserved' => 160, 'used' => 0]);
    LeaveRequest::factory()->for($this->first->contracts()->first()->leaveBalances()->first())->create([
        'type' => 'day', 'status' => LeaveRequestStatus::Accepted->value, 'start_at' => '2026-11-03', 'end_at' => '2026-11-04',
    ]);

    $alternatives = app(LeaveAlternativeEmployees::class);
    $ids = fn (?LeaveWindow $window): array => $alternatives->for($this->employee, $window)->pluck('id')->map(fn ($id) => (int) $id)->all();

    expect($ids(null))->toContain($this->first->id, $this->second->id)
        ->and($ids(LeaveWindow::days('2026-11-04', '2026-11-05')))->toContain($this->second->id)->not->toContain($this->first->id)
        ->and($ids(LeaveWindow::days('2026-11-10', '2026-11-11')))->toContain($this->first->id, $this->second->id);
});

test('a request cannot name a colleague who is away in its period', function () {
    $balance = $this->first->contracts()->first()->leaveBalances()->create(['type_id' => LeaveTypeCases::Annual, 'deserved' => 160, 'used' => 0]);
    LeaveRequest::factory()->for($balance)->create([
        'type' => 'day', 'status' => LeaveRequestStatus::Accepted->value, 'start_at' => '2026-11-03', 'end_at' => '2026-11-03',
    ]);

    expect(fn () => ($this->request)($this->first->id))->toThrow(ValidationException::class);
    expect(LeaveRequest::query()->where('leave_balance_id', $this->balance->id)->count())->toBe(0);

    expect(($this->request)($this->second->id)->alternative_employee_id)->toBe($this->second->id);
});
