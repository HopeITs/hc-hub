<?php

use Modules\Hr\Models\Employee;
use Modules\Tracking\Models\AnnualPlan;
use Modules\Tracking\Models\Milestone;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\Task;

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
    Milestone::factory()->create(['responsible_user_id' => $employee->id]);
    $task = Task::factory()->create();
    $task->taskAssignees()->delete();
    $task->taskAssignees()->create(['employee_id' => $employee->id]);

    $loaded = Employee::query()
        ->with(['managedAnnualPlans', 'projects', 'responsibleMilestones', 'assignedTasks'])
        ->findOrFail($employee->id);

    expect($loaded->managedAnnualPlans)->toHaveCount(1)
        ->and($loaded->projects)->toHaveCount(1)
        ->and($loaded->responsibleMilestones)->toHaveCount(1)
        ->and($loaded->assignedTasks->modelKeys())->toBe([$task->id]);

    foreach (['managedAnnualPlans', 'projects', 'responsibleMilestones', 'assignedTasks'] as $relation) {
        expect(Employee::query()->whereKey([$employee->id, $other->id])->whereHas($relation)->pluck('id')->all())
            ->toBe([$employee->id]);
    }
});
