<?php

use App\Models\Role;
use Carbon\CarbonImmutable;
use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Support\Inbox\DeskAgenda;
use Spatie\Permission\Models\Permission;

/*
| Needs you also lists what waits on the viewer in HR (leave to confirm, profile
| changes, recruitment steps), read from the HR work inbox with its own rules:
| a person with no HR right sees none of it, and Tracking decisions are unchanged.
*/

beforeEach(function () {
    $this->employee = Employee::factory()->create();
    $this->hr = User::factory()->create(['employee_id' => $this->employee->id]);
    $this->hr->assignRole(Role::findOrCreate('HR-SYR', 'web'));
    $this->hr->givePermissionTo(Permission::findOrCreate('leaves.review', 'web'));
    $this->hr = $this->hr->fresh();

    $this->decidedLeave = function (): LeaveRequest {
        $person = Employee::factory()->create(['first_name_en' => 'Layla', 'last_name_en' => 'Nassar']);
        $balance = LeaveBalance::factory()->for(Contract::factory()->for($person)->create(['is_active' => true]))->create();

        return LeaveRequest::factory()->for($balance)->create([
            'status' => LeaveRequestStatus::Accepted->value,
            'approved_by' => Employee::factory()->create()->id,
            'approved_at' => now(),
        ]);
    };
});

test('a leave request waiting for the viewer in HR shows in Needs you with its own link', function () {
    $leave = ($this->decidedLeave)();

    Livewire::actingAs($this->hr)->test('desk.panels.needs', ['size' => 'L'])
        ->assertSee('Layla Nassar')
        ->assertSee(__('desk.decision_action.leave_review'))
        ->assertSee(route('hr.leave.show', ['leaveRequest' => $leave->id]), false);
});

test('someone without the HR right does not see it', function () {
    ($this->decidedLeave)();
    $other = User::factory()->create(['employee_id' => Employee::factory()->create()->id]);

    Livewire::actingAs($other)->test('desk.panels.needs', ['size' => 'L'])
        ->assertDontSee('Layla Nassar');
});

test('the Desk count includes the HR decision', function () {
    ($this->decidedLeave)();

    Livewire::actingAs($this->hr)->test('pages::desk.index')
        ->assertSet('needsCount', ['total' => 1, 'late' => 0]);
});

test('the HR agenda never reaches back before today, so nothing is overdue from it', function () {
    $today = CarbonImmutable::today();

    expect(app(DeskAgenda::class)->between($this->hr, $today->subMonths(2), $today->subDay()))->toBeEmpty();
});
