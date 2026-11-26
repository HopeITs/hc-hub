<?php

use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
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
use Spatie\Permission\Models\Permission;

/*
| Hub 7.10d (items 2, 4, 5 and the dates half of 3): the request dialog
| checks and shows the priced summary before anything is sent, hourly leave is
| for annual leave between 09:00 and 21:00, the covering-colleague list follows
| the dates, and a sent request leaves a "submitted" card with the next step.
| "Today" is Wednesday 2026-10-21; 3 Nov 2026 is a Tuesday.
*/

beforeEach(function () {
    $this->travelTo('2026-10-21 10:00:00');
    Notification::fake();

    LeaveType::query()->find(LeaveTypeCases::Annual) ?? LeaveType::factory()->create(['id' => LeaveTypeCases::Annual]);
    LeaveType::query()->find(LeaveTypeCases::FullSick) ?? LeaveType::factory()->create(['id' => LeaveTypeCases::FullSick, 'base_days' => 5]);
    ContractType::query()->find(1) ?? ContractType::factory()->create(['id' => 1]);

    $city = City::factory()->create();
    $place = ['city_id' => $city->id, 'program_id' => Program::factory()->unassigned()->create()->id, 'department_id' => Department::factory()->create()->id];

    $managerPosition = Position::factory()->create($place);
    $managerEmployee = Employee::factory()->create(['city_id' => $city->id]);
    Contract::factory()->for($managerEmployee)->create(['position_id' => $managerPosition->id, 'contract_type_id' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]);

    $this->employee = Employee::factory()->create(['city_id' => $city->id]);
    $contract = Contract::factory()->for($this->employee)->create([
        'position_id' => Position::factory()->create($place + ['direct_manager_id' => $managerPosition->id])->id,
        'contract_type_id' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'weekly_hours' => 40, 'is_active' => true,
    ]);
    $this->balance = LeaveBalance::factory()->for($contract)->create(['type_id' => LeaveTypeCases::Annual, 'deserved' => 160]);
    $this->sickBalance = LeaveBalance::factory()->for($contract)->create(['type_id' => LeaveTypeCases::FullSick, 'deserved' => 40]);

    $this->requester = User::factory()->create(['employee_id' => $this->employee->id]);
    $this->requester->givePermissionTo(Permission::findOrCreate('leaves.view-own', 'web'), Permission::findOrCreate('leaves.create', 'web'));
    $this->requester = $this->requester->fresh();

    $this->colleague = Employee::factory()->create(['city_id' => $city->id]);
    Contract::factory()->for($this->colleague)->create(['position_id' => Position::factory()->create($place)->id, 'contract_type_id' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]);

    $this->hourly = fn (string $from, string $to, ?LeaveBalance $balance = null): array => [
        'type' => 'hour', 'leave_balance_id' => ($balance ?? $this->balance)->id, 'leave_date' => '2026-11-03',
        'start_hour' => $from, 'end_hour' => $to, 'reason' => 'Dentist appointment.',
    ];
    $this->dialog = fn () => Livewire::actingAs($this->requester)->test('hr::leave.index')
        ->call('openRequestForm')
        ->set('leaveBalanceId', (string) $this->balance->id)
        ->set('startAt', '2026-11-03')
        ->set('endAt', '2026-11-03')
        ->set('reason', 'Family visit out of town.');
});

test('hourly leave is refused on a leave type other than annual', function () {
    $save = fn (array $input) => app(SaveLeaveRequest::class)->create($this->requester, null, $input);

    expect(fn () => $save(($this->hourly)('10:00', '11:00', $this->sickBalance)))->toThrow(ValidationException::class);
    expect(LeaveRequest::query()->count())->toBe(0);

    expect($save(($this->hourly)('10:00', '11:00'))->type)->toBe('hour');
});

test('hourly leave must fall between 09:00 and 21:00', function (string $from, string $to) {
    expect(fn () => app(SaveLeaveRequest::class)->create($this->requester, null, ($this->hourly)($from, $to)))
        ->toThrow(ValidationException::class);

    expect(LeaveRequest::query()->count())->toBe(0);
})->with([
    'starts before nine' => ['08:00', '10:00'],
    'ends after nine in the evening' => ['20:00', '21:30'],
]);

test('the unit choice goes back to days when the leave type is not annual', function () {
    $dialog = ($this->dialog)()
        ->set('requestType', 'hour')
        ->assertSet('requestType', 'hour')
        ->set('leaveBalanceId', (string) $this->sickBalance->id)
        ->assertSet('requestType', 'day');

    expect(collect($dialog->instance()->requestBalances)->pluck('annual', 'id')->all())
        ->toBe([$this->balance->id => true, $this->sickBalance->id => false]);
});

test('sending the form first shows the priced summary and writes nothing', function () {
    ($this->dialog)()
        ->call('reviewRequest')
        ->assertHasNoErrors()
        ->assertSet('confirmingRequest', true)
        ->assertSeeHtml('data-leave-confirm')
        ->assertSeeHtml('data-leave-impact')
        ->call('backToRequestForm')
        ->assertSet('confirmingRequest', false)
        ->assertSeeHtml('data-leave-request-form');

    expect(LeaveRequest::query()->count())->toBe(0);
});

test('a form with errors is not shown for confirmation', function () {
    ($this->dialog)()
        ->set('reason', 'ab')
        ->call('reviewRequest')
        ->assertHasErrors(['reason'])
        ->assertSet('confirmingRequest', false);

    expect(LeaveRequest::query()->count())->toBe(0);
});

test('a reason of three characters is enough, two are not', function () {
    $save = fn (string $reason) => app(SaveLeaveRequest::class)->create($this->requester, null, [
        'type' => 'day', 'leave_balance_id' => $this->balance->id, 'start_at' => '2026-11-03', 'end_at' => '2026-11-03', 'reason' => $reason,
    ]);

    expect(fn () => $save('ab'))->toThrow(ValidationException::class);
    expect($save('abc')->reason)->toBe('abc');
});

test('after the send the dialog shows a submitted card with the next step', function () {
    $dialog = ($this->dialog)()
        ->call('reviewRequest')
        ->call('saveRequest')
        ->assertHasNoErrors()
        ->assertSet('showRequestModal', true)
        ->assertSet('confirmingRequest', false)
        ->assertSeeHtml('data-leave-submitted')
        ->assertSeeHtml('data-leave-next-step')
        ->assertDontSeeHtml('data-leave-request-form');

    $leave = LeaveRequest::query()->sole();

    expect($leave->status)->toBe(LeaveRequestStatus::Pending->value)
        ->and($dialog->get('submittedRequestId'))->toBe($leave->id);

    $dialog->call('submitRequest')->assertSet('showRequestModal', false)->assertSet('submittedRequestId', null);
    expect(LeaveRequest::query()->count())->toBe(1);
});

test('the covering colleagues follow the dates and a choice the new dates rule out is cleared', function () {
    $away = LeaveBalance::factory()->for($this->colleague->contracts()->first())->create(['type_id' => LeaveTypeCases::Annual, 'deserved' => 160]);
    LeaveRequest::factory()->for($away)->create(['type' => 'day', 'status' => LeaveRequestStatus::Accepted->value, 'start_at' => '2026-11-10', 'end_at' => '2026-11-11']);

    $dialog = ($this->dialog)()
        ->set('alternativeEmployee', (string) $this->colleague->id);

    expect($dialog->instance()->alternativeOptions)->toHaveKey($this->colleague->id);

    $dialog->set('startAt', '2026-11-10')->set('endAt', '2026-11-10')
        ->assertSet('alternativeEmployee', '');

    expect($dialog->instance()->alternativeOptions)->not->toHaveKey($this->colleague->id);

    $dialog->set('startAt', '2026-11-17')->set('endAt', '2026-11-17');

    expect($dialog->instance()->alternativeOptions)->toHaveKey($this->colleague->id);
});
