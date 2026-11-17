<?php

use App\Models\Activity;
use App\Notifications\SubjectLinks;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;

test('creating and changing an employee is recorded without notifying anyone', function () {
    Queue::fake();

    $employee = Employee::factory()->create(['first_name_en' => 'Sami', 'last_name_en' => 'Haddad']);
    $employee->update(['first_name_en' => 'Samir']);
    $employee->update(['is_active' => ! $employee->is_active]);

    $events = Activity::query()->where('subject_type', 'employee')->where('subject_id', $employee->id)->orderBy('id')->get();

    expect($events->pluck('event')->all())->toBe(['employee.created', 'employee.updated', 'employee.status_changed'])
        ->and($events->pluck('module')->unique()->all())->toBe(['hr-employees'])
        ->and($events->every(fn (Activity $activity): bool => ! $activity->shouldNotify()))->toBeTrue()
        ->and($events[1]->changes)->toHaveKey('first_name_en');

    Queue::assertNothingPushed();
});

test('identity, contact and security columns never reach the employee log', function () {
    $employee = Employee::factory()->create();

    $employee->update([
        'national_number' => '01234567890',
        'personal_phone' => '+963900000000',
        'personal_email' => 'private@example.com',
        'address_en' => 'Secret street',
        'last_name_en' => 'Visible',
    ]);

    $changes = Activity::query()->where('subject_type', 'employee')->where('event', 'employee.updated')->latest('id')->firstOrFail()->changes;

    expect($changes)->toHaveKey('last_name_en')
        ->and(array_keys($changes))->not->toContain('national_number', 'personal_phone', 'personal_email', 'address_en');
});

test('a leave decision is recorded as approved without the free-text reason', function () {
    $balance = LeaveBalance::factory()->create();
    $request = LeaveRequest::factory()->create(['leave_balance_id' => $balance->id, 'reason' => 'Medical details']);

    $request->update(['status' => LeaveRequestStatus::Accepted->value, 'reason' => 'Changed medical details']);

    $activity = Activity::query()->where('subject_type', 'leave_request')->where('event', 'leave_request.approved')->firstOrFail();

    expect($activity->module)->toBe('hr-leave')
        ->and($activity->changes)->toHaveKey('status')
        ->and($activity->changes)->not->toHaveKey('reason')
        ->and($activity->properties['employee_id'])->toBe($balance->contract->employee_id);
});

test('a contract change is recorded without pay terms', function () {
    $contract = Contract::factory()->create();

    $contract->update(['salary' => '2500', 'place' => $contract->place === 1 ? 2 : 1]);

    $activity = Activity::query()->where('subject_type', 'contract')->where('event', 'contract.updated')->firstOrFail();

    expect($activity->module)->toBe('hr-contracts')
        ->and($activity->changes)->toHaveKey('place')
        ->and($activity->changes)->not->toHaveKey('salary');
});

test('the activity log leads each HR subject to its page', function () {
    $employee = Employee::factory()->create(['username' => 'sami.haddad']);
    $contract = Contract::factory()->for($employee)->create();
    $request = LeaveRequest::factory()->create(['leave_balance_id' => LeaveBalance::factory()->create(['contract_id' => $contract->id])->id]);
    $links = app(SubjectLinks::class);

    expect($links->routeFor('employee', $employee->id))->toBe(route('hr.employees.show', $employee))
        ->and($links->routeFor('contract', $contract->id))->toBe(route('hr.contracts.show', $contract->id))
        ->and($links->routeFor('leave_request', $request->id))->toBe(route('hr.employees.show', $employee));
});

test('an employee without a username leads to the directory instead of a broken link', function () {
    $employee = Employee::factory()->create(['username' => null]);

    expect(app(SubjectLinks::class)->routeFor('employee', $employee->id))->toBe(route('hr.employees.index'));
});

test('the timeline shows HR activity only to who may read the activity log', function () {
    $employee = Employee::factory()->create(['first_name_en' => 'Sami', 'last_name_en' => 'Haddad']);
    $employee->update(['last_name_en' => 'Renamed']);

    $manager = userWithRole('General Manager');
    $officer = userWithRole('Team Member');

    Livewire::actingAs($manager)->test('activity.timeline', ['subjects' => ['employee' => [$employee->id]]])
        ->assertSeeText('Sami Renamed');

    Livewire::actingAs($officer)->test('activity.timeline', ['subjects' => ['employee' => [$employee->id]]])
        ->assertDontSeeText('Sami Renamed')
        ->assertSeeText(__('activity.empty'));
});
