<?php

use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Enums\LeaveTypeCases;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Models\LeaveType;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Leave\LeaveAlternativeEmployees;
use Modules\Hr\Support\Leave\LeaveWindow;
use Modules\Hr\Support\Settings\HrAccess;
use Spatie\Permission\Models\Permission;

/*
| The alternative employee of a leave request: only colleagues free for the
| whole period are offered and accepted, an edit keeps the alternative it
| already has, and whoever accepts may name another one (the requester is told).
| "Today" is Wednesday 2026-10-21; 2026-11-02 is a Monday.
*/

beforeEach(function () {
    HrAccess::flush();
    $this->travelTo('2026-10-21 10:00:00');

    LeaveType::query()->find(LeaveTypeCases::Annual) ?? LeaveType::factory()->create(['id' => LeaveTypeCases::Annual]);
    ContractType::query()->find(1) ?? ContractType::factory()->create(['id' => 1]);

    $this->contractFields = ['contract_type_id' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'weekly_hours' => 40, 'is_active' => true];

    $this->city = City::factory()->create();
    $this->grade = JobLevel::factory()->create(['grade' => 6]);
    $this->program = Program::factory()->unassigned()->create();
    $this->department = Department::factory()->create();

    $this->seat = fn (): Employee => tap(Employee::factory()->create(['city_id' => $this->city->id]), function (Employee $employee): void {
        Contract::factory()->for($employee)->for($this->position)->create($this->contractFields);
    });

    $this->position = Position::factory()->create([
        'city_id' => $this->city->id, 'job_level_id' => $this->grade->id,
        'program_id' => $this->program->id, 'department_id' => $this->department->id,
    ]);

    $this->requester = ($this->seat)();
    $this->free = ($this->seat)();
    $this->busy = ($this->seat)();

    $this->balanceOf = fn (Employee $employee): LeaveBalance => LeaveBalance::query()->where('contract_id', $employee->contracts()->value('id'))->first()
        ?? LeaveBalance::factory()->create(['contract_id' => $employee->contracts()->value('id'), 'type_id' => LeaveTypeCases::Annual, 'deserved' => 160]);

    $this->awayOn = fn (Employee $employee, string $start, string $end, int $status = 1, string $type = 'day'): LeaveRequest => LeaveRequest::factory()->for(($this->balanceOf)($employee))->create([
        'status' => $status, 'type' => $type, 'start_at' => $start, 'end_at' => $end,
    ]);
});

test('a colleague on accepted or pending leave in the period is left out, a rejected one is not', function () {
    $pending = ($this->seat)();
    $rejected = ($this->seat)();
    $before = ($this->seat)();

    ($this->awayOn)($this->busy, '2026-11-04', '2026-11-08', LeaveRequestStatus::Accepted->value);
    ($this->awayOn)($pending, '2026-10-30', '2026-11-02', LeaveRequestStatus::Pending->value);
    ($this->awayOn)($rejected, '2026-11-02', '2026-11-03', LeaveRequestStatus::Rejected->value);
    ($this->awayOn)($before, '2026-10-28', '2026-11-01', LeaveRequestStatus::Accepted->value);

    $list = fn (?LeaveWindow $period = null): array => app(LeaveAlternativeEmployees::class)
        ->for($this->requester->fresh(), $period)->modelKeys();

    $offered = $list(LeaveWindow::days('2026-11-02', '2026-11-04'));

    expect($offered)->toContain($this->free->id, $rejected->id, $before->id)
        ->not->toContain($this->busy->id, $pending->id)
        ->and($list())->toContain($this->busy->id, $pending->id);
});

test('hourly leave clashes only when the hours meet', function () {
    $morning = ($this->seat)();
    $afternoon = ($this->seat)();

    ($this->awayOn)($morning, '2026-11-02 09:00:00', '2026-11-02 11:00:00', LeaveRequestStatus::Accepted->value, 'hour');
    ($this->awayOn)($afternoon, '2026-11-02 13:00:00', '2026-11-02 15:00:00', LeaveRequestStatus::Accepted->value, 'hour');

    $offered = app(LeaveAlternativeEmployees::class)->for($this->requester->fresh(), LeaveWindow::hours('2026-11-02', '10:00', '12:00'))->modelKeys();

    expect($offered)->toContain($afternoon->id)->not->toContain($morning->id);
});

test('the request form lists and validates the alternative for the dates typed', function () {
    ($this->awayOn)($this->busy, '2026-11-02', '2026-11-02', LeaveRequestStatus::Accepted->value);
    $user = User::factory()->create(['employee_id' => $this->requester->id]);

    foreach (['leaves.view-own', 'leaves.create'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    $balance = ($this->balanceOf)($this->requester);

    $page = Livewire::actingAs($user->fresh())->test('hr::leave.index')
        ->call('openRequestForm')
        ->set('leaveBalanceId', (string) $balance->id)
        ->set('startAt', '2026-11-02')
        ->set('endAt', '2026-11-03')
        ->set('reason', 'Cover by someone who is away.');

    expect(array_keys($page->instance()->alternativeOptions))->toContain($this->free->id)->not->toContain($this->busy->id)
        // Each colleague's position goes with the name: two of them may share one.
        ->and($page->instance()->alternativeOptions[$this->free->id])->toContain($this->free->name)->toContain(' · ');

    $page->set('alternativeEmployee', (string) $this->busy->id)
        ->call('saveRequest')
        ->assertHasErrors(['alternativeEmployee']);

    expect(LeaveRequest::query()->where('alternative_employee_id', $this->busy->id)->exists())->toBeFalse();
});

test('moving the dates onto the alternative\'s leave drops the choice', function () {
    ($this->awayOn)($this->busy, '2026-11-02', '2026-11-02', LeaveRequestStatus::Pending->value);
    $user = User::factory()->create(['employee_id' => $this->requester->id]);
    $user->givePermissionTo(Permission::findOrCreate('leaves.create', 'web'));

    Livewire::actingAs($user->fresh())->test('hr::leave.index')
        ->call('openRequestForm')
        ->set('startAt', '2026-11-09')
        ->set('endAt', '2026-11-10')
        ->set('alternativeEmployee', (string) $this->busy->id)
        ->assertSet('alternativeEmployee', (string) $this->busy->id)
        ->set('startAt', '2026-11-02')
        ->set('endAt', '2026-11-02')
        ->assertSet('alternativeEmployee', '');
});

describe('the decision', function () {
    beforeEach(function () {
        $this->named = ($this->seat)();
        $this->other = ($this->seat)();

        $managerEmployee = Employee::factory()->create(['city_id' => $this->city->id]);
        $managerPosition = Position::factory()->create(['city_id' => $this->city->id]);
        Contract::factory()->for($managerEmployee)->for($managerPosition)->create($this->contractFields);
        $this->position->update(['direct_manager_id' => $managerPosition->id]);
        $this->manager = User::factory()->create(['employee_id' => $managerEmployee->id])->fresh();

        $this->requesterAccount = User::factory()->create(['employee_id' => $this->requester->id]);

        $this->decision = LeaveRequest::factory()->for(($this->balanceOf)($this->requester))->create([
            'status' => LeaveRequestStatus::Pending->value, 'start_at' => '2026-11-03', 'end_at' => '2026-11-03',
            'alternative_employee_id' => $this->named->id,
        ]);

        Notification::fake();

        $this->changeNotices = fn (): int => Notification::sent($this->requesterAccount, AppNotification::class)
            ->filter(fn (AppNotification $notification): bool => $notification->payload->key === 'hr_leave.notifications.alternative_changed')
            ->count();
    });

    test('accepting with another alternative changes it and tells the requester', function () {
        Livewire::actingAs($this->manager)->test('hr::leave.show', ['leaveRequest' => $this->decision->id])
            ->call('openDecide', 'accept')
            ->assertSet('decisionAlternative', (string) $this->named->id)
            ->assertSeeHtml('data-decision-alternative')
            ->set('decisionAlternative', (string) $this->other->id)
            ->call('decide')
            ->assertHasNoErrors();

        expect($this->decision->fresh())
            ->status->toBe(LeaveRequestStatus::Accepted->value)
            ->alternative_employee_id->toBe($this->other->id)
            ->and(($this->changeNotices)())->toBe(1);
    });

    test('an unchanged alternative sends no change notice and a rejection never changes it', function () {
        Livewire::actingAs($this->manager)->test('hr::leave.show', ['leaveRequest' => $this->decision->id])
            ->call('openDecide', 'accept')
            ->call('decide')
            ->assertHasNoErrors();

        expect($this->decision->fresh()->alternative_employee_id)->toBe($this->named->id)
            ->and(($this->changeNotices)())->toBe(0);

        $second = LeaveRequest::factory()->for(($this->balanceOf)($this->requester))->create([
            'status' => LeaveRequestStatus::Pending->value, 'start_at' => '2026-11-10', 'end_at' => '2026-11-10',
            'alternative_employee_id' => $this->named->id,
        ]);

        Livewire::actingAs($this->manager)->test('hr::leave.show', ['leaveRequest' => $second->id])
            ->call('openDecide', 'reject')
            ->set('decisionAlternative', (string) $this->other->id)
            ->set('rejectReason', 'No cover.')
            ->call('decide')
            ->assertHasNoErrors();

        expect($second->fresh())->status->toBe(LeaveRequestStatus::Rejected->value)->alternative_employee_id->toBe($this->named->id);
    });

    test('the decider may not name a colleague who is away, and a busy one already named may be kept', function () {
        ($this->awayOn)($this->busy, '2026-11-03', '2026-11-03', LeaveRequestStatus::Accepted->value);

        Livewire::actingAs($this->manager)->test('hr::leave.show', ['leaveRequest' => $this->decision->id])
            ->call('openDecide', 'accept')
            ->set('decisionAlternative', (string) $this->busy->id)
            ->call('decide')
            ->assertHasErrors(['alternative']);

        expect($this->decision->fresh())->status->toBe(LeaveRequestStatus::Pending->value);

        $this->decision->update(['alternative_employee_id' => $this->busy->id]);

        Livewire::actingAs($this->manager)->test('hr::leave.show', ['leaveRequest' => $this->decision->id])
            ->call('openDecide', 'accept')
            ->assertSeeHtml('data-decision-alternative-busy')
            ->call('decide')
            ->assertHasNoErrors();

        expect($this->decision->fresh())->alternative_employee_id->toBe($this->busy->id);
    });
});
