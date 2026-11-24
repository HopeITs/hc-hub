<?php

use Modules\Core\Models\Center;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;
use Modules\Tracking\Enums\DonorAttribution;
use Modules\Tracking\Models\AnnualPlan;
use Modules\Tracking\Models\IndicatorProgress;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\Report;
use Modules\Tracking\Models\Result;
use Modules\Tracking\Models\StrategicPillar;
use Modules\Tracking\Models\Task;
use Modules\Tracking\Support\TrackingDuties;

/*
 * Relations a module registers on another module's model
 * (resolveRelationUsing in its ServiceProvider) behave like declared ones:
 * eager loading, whereHas() and lazy reads under strict mode.
 */

test('tracking relations on an employee eager load and filter', function () {
    $employee = Employee::find(insertHrEmployee());
    $other = Employee::find(insertHrEmployee());

    AnnualPlan::factory()->create(['manager_id' => $employee->id]);
    Project::factory()->create(['user_id' => $employee->id]);
    $task = Task::factory()->create();
    $task->taskAssignees()->delete();
    $task->taskAssignees()->create(['employee_id' => $employee->id]);

    $loaded = Employee::query()
        ->with(['managedAnnualPlans', 'projects', 'assignedTasks'])
        ->findOrFail($employee->id);

    expect($loaded->managedAnnualPlans)->toHaveCount(1)
        ->and($loaded->projects)->toHaveCount(1)
        ->and($loaded->assignedTasks->modelKeys())->toBe([$task->id]);

    foreach (['managedAnnualPlans', 'projects', 'assignedTasks'] as $relation) {
        expect(Employee::query()->whereKey([$employee->id, $other->id])->whereHas($relation)->pluck('id')->all())
            ->toBe([$employee->id]);
    }
});

test('a user reaches its hr employee and its tracking rows through the module relations', function () {
    $employeeId = insertHrEmployee();
    $user = User::factory()->create(['employee_id' => $employeeId]);
    $other = User::factory()->withoutEmployee()->create();

    Task::factory()->create(['created_by' => $user->id]);
    Report::factory()->create(['user_id' => $user->id]);
    IndicatorProgress::factory()->create(['recorded_by' => $user->id]);

    $loaded = User::query()->with(['employee', 'createdTasks', 'reports', 'indicatorProgress'])->findOrFail($user->id);

    expect($loaded->employee?->id)->toBe($employeeId)
        ->and($loaded->createdTasks)->toHaveCount(1)
        ->and($loaded->reports)->toHaveCount(1)
        ->and($loaded->indicatorProgress)->toHaveCount(1)
        ->and(User::query()->whereKey([$user->id, $other->id])->whereHas('employee')->pluck('id')->all())->toBe([$user->id]);

    $user->employee()->dissociate();
    expect($user->employee_id)->toBeNull()->and($user->relationLoaded('employee'))->toBeTrue();
});

test('an account loses access once its employee is inactive', function () {
    $user = User::factory()->create(['employee_id' => insertHrEmployee()]);
    $system = User::factory()->withoutEmployee()->create();

    expect($user->hasLostAccess())->toBeFalse()
        ->and($system->hasLostAccess())->toBeFalse();

    Employee::query()->whereKey($user->employee_id)->update(['is_active' => false]);

    expect($user->hasLostAccess())->toBeTrue()
        ->and(User::query()->whereKey([$user->id, $system->id])->withoutActiveAccount()->pluck('id')->all())->toBe([$user->id]);
});

test('a user is found by the name hr holds for them', function () {
    $user = User::factory()->create(['employee_id' => namedEmployee('Zaid Haddad')]);
    User::factory()->create(['employee_id' => namedEmployee('Amal Saleh')]);

    expect(User::query()->whereNameOrEmailLike('zaid')->pluck('id')->all())->toBe([$user->id]);
});

test('a program reaches its manager, strategy, pillar and tracking rows through the module relations', function () {
    $pillar = StrategicPillar::factory()->create();
    $managerId = insertHrEmployee();
    $program = Program::factory()->create([
        'manager_id' => $managerId,
        'strategy_id' => $pillar->strategy_id,
        'strategic_pillar_id' => $pillar->id,
    ]);
    $other = Program::factory()->unassigned()->create();

    Project::factory()->create(['program_id' => $program->id]);
    AnnualPlan::factory()->create(['program_id' => $program->id]);
    Result::factory()->create(['program_id' => $program->id]);
    Result::factory()->create(['program_id' => $program->id, 'proposal_id' => Proposal::factory()]);

    $loaded = Program::query()->with(['manager', 'strategy', 'pillar', 'projects', 'annualPlans', 'results'])->findOrFail($program->id);

    expect($loaded->manager?->id)->toBe($managerId)
        ->and($loaded->strategy?->id)->toBe($pillar->strategy_id)
        ->and($loaded->pillar?->id)->toBe($pillar->id)
        ->and($loaded->projects)->toHaveCount(1)
        ->and($loaded->annualPlans)->toHaveCount(1)
        ->and($loaded->results)->toHaveCount(1);

    foreach (['manager', 'strategy', 'pillar', 'projects', 'annualPlans', 'results'] as $relation) {
        expect(Program::query()->whereKey([$program->id, $other->id])->whereHas($relation)->pluck('id')->all())
            ->toBe([$program->id]);
    }
});

test('tracking casts a program donor attribution to its enum', function () {
    $program = Program::factory()->create(['donor_attribution' => DonorAttribution::Split]);

    expect($program->fresh()->donor_attribution)->toBe(DonorAttribution::Split)
        ->and((new Program)->donor_attribution)->toBe(DonorAttribution::Shared);
});

test('a city and a center reach their hr positions, employees and contracts', function () {
    $city = City::factory()->create();
    $center = Center::factory()->create(['city_id' => $city->id]);
    $employeeId = insertHrEmployee($city->id);
    $positionId = insertHrPosition(insertHrDepartment(), centerId: $center->id, cityId: $city->id);
    $contractId = insertHrContract($employeeId, $positionId);

    $loadedCity = City::query()->with(['positions', 'employees'])->findOrFail($city->id);
    $loadedCenter = Center::query()->with(['positions', 'contracts'])->findOrFail($center->id);

    expect($loadedCity->positions->modelKeys())->toBe([$positionId])
        ->and($loadedCity->employees->modelKeys())->toBe([$employeeId])
        ->and($loadedCenter->positions->modelKeys())->toBe([$positionId])
        ->and($loadedCenter->contracts->modelKeys())->toBe([$contractId])
        ->and(Center::query()->whereHas('contracts')->pluck('id')->all())->toBe([$center->id]);
});

test('tracking duties list the projects a user owns or manages through a program', function () {
    $employeeId = insertHrEmployee();
    $user = User::factory()->create(['employee_id' => $employeeId]);
    $program = Program::factory()->create(['manager_id' => $employeeId]);

    $owned = Project::factory()->create(['user_id' => $employeeId]);
    $managed = Project::factory()->create(['program_id' => $program->id]);
    Project::factory()->create();

    $ids = TrackingDuties::for($user)->ownedProjectIds();
    sort($ids);

    expect($ids)->toBe([$owned->id, $managed->id])
        ->and(TrackingDuties::for(User::factory()->withoutEmployee()->create())->ownedProjectIds())->toBe([])
        ->and(TrackingDuties::for($user)->hasReportingDuties())->toBeFalse();
});
