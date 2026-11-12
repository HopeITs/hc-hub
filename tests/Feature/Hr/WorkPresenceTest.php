<?php

use App\Models\Role;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Employees\WorkPresence;
use Modules\Hr\Support\Settings\HrAccess;
use Spatie\Permission\Models\Permission;

/*
| The presence dot (WorkPresence): online is an account seen in the last five
| minutes (users.last_seen_at, stamped by RecordPresence). Why someone is
| offline is told only to a viewer who may see their absences on the team
| calendar (TeamCoverage::canSeeAbsencesOf); everyone else reads "Offline".
*/

beforeEach(function () {
    HrAccess::flush();

    $this->department = Department::factory()->create();

    /** A person with an account and a running contract in $department. */
    $this->person = function (?Department $department = null, array $account = []): array {
        $employee = Employee::factory()->create(['username' => fake()->unique()->userName()]);
        $position = Position::factory()->create(['department_id' => ($department ?? $this->department)->id]);
        $contract = Contract::factory()->for($employee)->for($position)->create();
        $user = User::factory()->create(['employee_id' => $employee->id, ...$account]);
        $user->givePermissionTo(Permission::findOrCreate('leaves.view-own', 'web'));

        return ['employee' => $employee, 'contract' => $contract, 'user' => $user->fresh()];
    };

    $this->onLeaveToday = fn (Contract $contract): LeaveRequest => LeaveRequest::factory()
        ->for(LeaveBalance::factory()->for($contract))
        ->create([
            'type' => 'day',
            'status' => LeaveRequestStatus::Accepted->value,
            'start_at' => now()->startOfDay(),
            'end_at' => now()->startOfDay(),
        ]);
});

test('a person seen in the last five minutes is online, and offline after that', function () {
    $viewer = ($this->person)();
    $recent = ($this->person)(account: ['last_seen_at' => now()->subMinutes(2)]);
    $stale = ($this->person)(account: ['last_seen_at' => now()->subMinutes(20)]);
    $never = ($this->person)();

    $presence = app(WorkPresence::class)->for($viewer['user'], [$recent['employee'], $stale['employee'], $never['employee']]);

    expect($presence[$recent['employee']->id])->toBe(['tone' => 'online', 'label' => 'Online'])
        ->and($presence[$stale['employee']->id])->toBe(['tone' => 'offline', 'label' => 'Offline'])
        ->and($presence[$never['employee']->id])->toBe(['tone' => 'offline', 'label' => 'Offline']);
});

test('a person without an account, or a former one, gets no dot', function () {
    $viewer = ($this->person)();
    $noAccount = Employee::factory()->create();
    $former = Employee::factory()->inactive()->create();
    User::factory()->create(['employee_id' => $former->id, 'last_seen_at' => now()]);

    $service = app(WorkPresence::class);

    expect($service->for($viewer['user'], [$noAccount, $former]))->toBe([])
        ->and($service->forOne($viewer['user'], null))->toBeNull();
});

test('a colleague who may see the absence reads "On leave"', function () {
    $colleague = ($this->person)();
    $lina = ($this->person)();
    ($this->onLeaveToday)($lina['contract']);

    expect(app(WorkPresence::class)->forOne($colleague['user'], $lina['employee']))
        ->toBe(['tone' => 'offline', 'label' => 'On leave']);
});

test('someone outside the person\'s team reads only "Offline"', function () {
    $stranger = ($this->person)(Department::factory()->create());
    $lina = ($this->person)();
    ($this->onLeaveToday)($lina['contract']);

    expect(app(WorkPresence::class)->forOne($stranger['user'], $lina['employee']))
        ->toBe(['tone' => 'offline', 'label' => 'Offline']);
});

test('a colleague without the team calendar permission reads only "Offline"', function () {
    $colleague = ($this->person)();
    $colleague['user']->revokePermissionTo('leaves.view-own');
    $lina = ($this->person)();
    ($this->onLeaveToday)($lina['contract']);

    expect(app(WorkPresence::class)->forOne($colleague['user']->fresh(), $lina['employee']))
        ->toBe(['tone' => 'offline', 'label' => 'Offline']);
});

test('a person on leave who is in the hub right now is online', function () {
    $colleague = ($this->person)();
    $lina = ($this->person)(account: ['last_seen_at' => now()]);
    ($this->onLeaveToday)($lina['contract']);

    expect(app(WorkPresence::class)->forOne($colleague['user'], $lina['employee'])['label'])->toBe('Online');
});

test('a colleague outside their fixed hours reads "Outside working hours"', function () {
    $this->travelTo('2026-11-11 18:00:00 UTC');

    $colleague = ($this->person)();
    $lina = ($this->person)();
    $lina['contract']->update(['work_day_from' => 0, 'work_day_to' => 6, 'work_hours_from' => '08:00', 'work_hours_to' => '16:00']);

    expect(app(WorkPresence::class)->forOne($colleague['user'], $lina['employee'])['label'])->toBe('Outside working hours');

    $this->travelTo('2026-11-11 07:00:00 UTC');

    expect(app(WorkPresence::class)->forOne($colleague['user'], $lina['employee'])['label'])->toBe('Offline');
});

test('a page of people costs the same queries as one person', function () {
    $viewer = ($this->person)();
    $people = collect(range(1, 6))->map(function () {
        $person = ($this->person)();
        ($this->onLeaveToday)($person['contract']);

        return $person['employee']->id;
    });

    $count = function (array $ids) use ($viewer): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        app(WorkPresence::class)->for($viewer['user'], $ids);
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };

    $count([$people->last()]);
    $one = $count([$people->first()]);

    expect($count($people->all()))->toBe($one)
        ->and($one)->toBeLessThanOrEqual(20);
});

test('the employee directory and the profile header draw the dot', function () {
    $viewer = ($this->person)()['user'];
    $viewer->givePermissionTo(Permission::findOrCreate('employees.view', 'web'));
    $viewer = $viewer->fresh();
    $lina = ($this->person)(account: ['last_seen_at' => now()]);
    $omar = ($this->person)();
    ($this->onLeaveToday)($omar['contract']);

    Livewire::actingAs($viewer)->test('hr::employees.index')
        ->assertSeeHtml('aria-label="Online" title="Online" data-presence="online"')
        ->assertSeeHtml('aria-label="On leave" title="On leave" data-presence="offline"');

    Livewire::actingAs($viewer)->test('hr::employees.show', ['employee' => $lina['employee']])
        ->assertSeeHtml('data-presence="online"');
});

test('a work inbox row carries the dot of the person it is about', function () {
    $reviewer = ($this->person)()['user'];
    $reviewer->assignRole(Role::findOrCreate('HR-SYR', 'web'));
    $reviewer->givePermissionTo(Permission::findOrCreate('leaves.review', 'web'));
    HrAccess::flush();

    $lina = ($this->person)(Department::factory()->create());
    ($this->onLeaveToday)($lina['contract'])->update(['approved_by' => Employee::factory()->create()->id, 'approved_at' => now()]);

    Livewire::actingAs($reviewer->fresh())->test('hr::inbox.index')
        ->assertSeeHtml('aria-label="On leave" title="On leave" data-presence="offline"');
});

test('the dot draws nothing without a status', function () {
    expect(trim(Blade::render('<x-hr::employees.presence :status="null" />')))->toBe('');
});
