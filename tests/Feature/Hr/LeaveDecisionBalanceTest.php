<?php

use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Enums\LeaveTypeCases;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Models\LeaveType;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Settings\HrAccess;

/*
| Whoever decides a pending leave request sees the balance it draws on: what
| is available, what this request takes, what the other pending requests take,
| and what is left. Advisory only. "Today" is Wednesday 2026-10-21.
*/

beforeEach(function () {
    HrAccess::flush();
    $this->travelTo('2026-10-21 10:00:00');

    LeaveType::query()->find(LeaveTypeCases::Annual) ?? LeaveType::factory()->create(['id' => LeaveTypeCases::Annual]);
    ContractType::query()->find(1) ?? ContractType::factory()->create(['id' => 1]);

    $contractFields = ['contract_type_id' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'weekly_hours' => 40, 'is_active' => true];

    $managerEmployee = Employee::factory()->create();
    $managerPosition = Position::factory()->create();
    Contract::factory()->for($managerEmployee)->for($managerPosition)->create($contractFields);
    $this->manager = User::factory()->create(['employee_id' => $managerEmployee->id])->fresh();

    $employee = Employee::factory()->create();
    $contract = Contract::factory()->for($employee)->for(Position::factory()->create(['direct_manager_id' => $managerPosition->id]))->create($contractFields);
    $this->balance = LeaveBalance::factory()->for($contract)->create(['type_id' => LeaveTypeCases::Annual, 'deserved' => 160]);

    $this->requestOn = fn (string $day): LeaveRequest => LeaveRequest::factory()->for($this->balance)->create([
        'status' => LeaveRequestStatus::Pending->value, 'start_at' => $day, 'end_at' => $day,
    ]);
});

test('the decide dialog shows the balance with the other pending requests and what is left', function () {
    $request = ($this->requestOn)('2026-11-03');
    ($this->requestOn)('2026-11-04');

    $page = Livewire::actingAs($this->manager)->test('hr::leave.show', ['leaveRequest' => $request->id])
        ->call('openDecide', 'accept')
        ->assertSeeHtml('data-decision-balance');

    $figures = $page->instance()->decisionBalance;

    expect($figures['requested'])->toBeGreaterThan(0.0)
        ->and($figures['pending'])->toBe($figures['requested'])
        ->and($figures['after'])->toBe(round($figures['available'] - $figures['requested'], 2))
        ->and($figures['short'])->toBeFalse();
});

test('a request larger than the balance is shown as short, and a decided one shows no balance', function () {
    $this->balance->update(['deserved' => 2]);
    $request = ($this->requestOn)('2026-11-03');

    $page = Livewire::actingAs($this->manager)->test('hr::leave.show', ['leaveRequest' => $request->id]);

    expect($page->instance()->decisionBalance['short'])->toBeTrue();

    $request->update(['status' => LeaveRequestStatus::Accepted->value]);

    expect(Livewire::actingAs($this->manager)->test('hr::leave.show', ['leaveRequest' => $request->id])->instance()->decisionBalance)->toBeNull();
});
