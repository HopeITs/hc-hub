<?php

use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveTypeCases;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Models\LeaveType;
use Spatie\Permission\Models\Permission;

/*
| The team calendar's "Request leave" opens the leave request dialog on the
| calendar itself, on the day shown, instead of sending the employee to My
| leaves. "Today" is Wednesday 2026-10-21; 3 Nov 2026 is a Tuesday.
*/

beforeEach(function () {
    $this->travelTo('2026-10-21 10:00:00');

    LeaveType::query()->find(LeaveTypeCases::Annual) ?? LeaveType::factory()->create(['id' => LeaveTypeCases::Annual]);
    ContractType::query()->find(1) ?? ContractType::factory()->create(['id' => 1]);

    $employee = Employee::factory()->create();
    $contract = Contract::factory()->for($employee)->create([
        'contract_type_id' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31',
        'weekly_hours' => 40, 'is_active' => true,
    ]);
    $this->balance = LeaveBalance::factory()->for($contract)->create(['type_id' => LeaveTypeCases::Annual, 'deserved' => 160]);

    $this->user = User::factory()->create(['employee_id' => $employee->id]);

    foreach (['leaves.view-own', 'leaves.create'] as $permission) {
        $this->user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    $this->user = $this->user->fresh();
});

test('request leave opens the dialog on the calendar, on the day shown', function () {
    Livewire::actingAs($this->user)->test('hr::leave.team-calendar')
        ->assertSeeHtml('data-test="calendar-request-leave"')
        ->assertDontSeeHtml('new=1')
        ->set('view', 'day')
        ->set('date', '2026-11-03')
        ->call('requestLeave')
        ->assertSet('showRequestModal', true)
        ->assertSet('startAt', '2026-11-03')
        ->assertSet('endAt', '2026-11-03')
        ->assertSeeHtml('data-leave-request-form');
});

test('a past day shown on the calendar is not offered as the leave day', function () {
    Livewire::actingAs($this->user)->test('hr::leave.team-calendar')
        ->set('view', 'day')
        ->set('date', '2026-10-01')
        ->call('requestLeave')
        ->assertSet('showRequestModal', true)
        ->assertSet('startAt', '2026-10-21');
});

test('a request sent from the calendar is saved without leaving it', function () {
    Livewire::actingAs($this->user)->test('hr::leave.team-calendar')
        ->set('view', 'day')
        ->set('date', '2026-11-03')
        ->call('requestLeave')
        ->set('leaveBalanceId', (string) $this->balance->id)
        ->set('reason', 'Family visit out of town.')
        ->call('reviewRequest')
        ->assertSet('confirmingRequest', true)
        ->call('saveRequest')
        ->assertHasNoErrors()
        ->assertSet('showRequestModal', true)
        ->assertSeeHtml('data-leave-submitted')
        ->call('submitRequest')
        ->assertSet('showRequestModal', false)
        ->assertNoRedirect();

    expect(LeaveRequest::query()->sole()->start_at->toDateString())->toBe('2026-11-03');
});

test('someone who may not request leave gets no button and no dialog', function () {
    $viewer = User::factory()->create(['employee_id' => Employee::factory()->create()->id]);
    $viewer->givePermissionTo(Permission::findOrCreate('leaves.view-own', 'web'));

    Livewire::actingAs($viewer->fresh())->test('hr::leave.team-calendar')
        ->assertDontSeeHtml('data-test="calendar-request-leave"')
        ->call('requestLeave')
        ->assertForbidden();
});
