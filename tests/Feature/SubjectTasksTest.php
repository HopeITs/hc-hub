<?php

use App\Support\Desk\DeskWork;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Tracking\Actions\Tasks\CreateTask;
use Modules\Tracking\Enums\TaskStatus;
use Modules\Tracking\Models\Task;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->departmentId = insertHrDepartment('Media');
    $this->otherDepartmentId = insertHrDepartment('Finance');
    $this->subjectEmployeeId = insertHrEmployee(firstNameEn: 'Subject', lastNameEn: 'Person');
    $this->assigneeEmployeeId = insertHrEmployee(firstNameEn: 'Assigned', lastNameEn: 'Person');
    $this->outsiderEmployeeId = insertHrEmployee(firstNameEn: 'Outside', lastNameEn: 'Person');
    $this->memberEmployeeId = insertHrEmployee(firstNameEn: 'Member', lastNameEn: 'Person');
    insertHrContract($this->memberEmployeeId, insertHrPosition($this->departmentId));

    $this->employeeTask = Task::factory()->create(['title' => 'Employee file task', 'source_type' => 'employee', 'source_id' => $this->subjectEmployeeId]);
    $this->employeeTask->taskAssignees()->delete();
    $this->employeeTask->taskAssignees()->create(['employee_id' => $this->assigneeEmployeeId]);

    $this->departmentTask = Task::factory()->create(['title' => 'Department file task', 'source_type' => 'department', 'source_id' => $this->departmentId]);
    $this->departmentTask->taskAssignees()->delete();
    $this->departmentTask->taskAssignees()->create(['employee_id' => $this->assigneeEmployeeId]);
});

function subjectTaskPayload(): array
{
    return [
        'newTaskTitle' => 'Follow up',
        'newTaskDescription' => '',
        'newTaskStatus' => TaskStatus::Todo->value,
        'newTaskPriority' => 'medium',
        'newTaskDueDate' => '',
        'newTaskAssignedTo' => [],
    ];
}

test('a task can be raised on an employee or a department', function (string $type) {
    $user = userWithRole('Managers', $this->outsiderEmployeeId);
    $sourceId = $type === 'employee' ? $this->subjectEmployeeId : $this->departmentId;

    $this->actingAs($user);
    $task = app(CreateTask::class)->handle($type, $sourceId, subjectTaskPayload(), $user->id);

    expect($task->source_type)->toBe($type)
        ->and($task->source_id)->toBe($sourceId)
        ->and($task->source)->not->toBeNull()
        ->and($task->activityProgramId())->toBeNull();
})->with(['employee', 'department']);

test('a task cannot be raised on a missing record or an unlisted source type', function () {
    $user = userWithRole('Managers');
    $this->actingAs($user);

    expect(fn () => app(CreateTask::class)->handle('employee', 999999, subjectTaskPayload(), $user->id))
        ->toThrow(ValidationException::class)
        ->and(fn () => app(CreateTask::class)->handle('user', $user->id, subjectTaskPayload(), $user->id))
        ->toThrow(ValidationException::class);
});

test('the subject tasks section lists and adds tasks for an employee', function () {
    $user = userWithRole('Managers', $this->outsiderEmployeeId);
    Permission::findOrCreate('employees.view');
    $user->givePermissionTo('employees.view');

    Livewire::actingAs($user)
        ->test('tasks.subject-tasks', ['subjectType' => 'employee', 'subjectId' => $this->subjectEmployeeId])
        ->assertSee('Employee file task')
        ->assertDontSee('Department file task')
        ->call('openAddTaskModal')
        ->set('newTaskTitle', 'Collect the signed form')
        ->call('addTask')
        ->assertSee('Collect the signed form');

    expect(Task::query()->forSubject('employee', $this->subjectEmployeeId)->count())->toBe(2);
});

test('the subject tasks section refuses a source type tasks cannot be raised on', function () {
    Livewire::actingAs(userWithRole('Managers'))
        ->test('tasks.subject-tasks', ['subjectType' => 'user', 'subjectId' => 1])
        ->assertNotFound();
});

describe('visibility of a task raised on an employee', function () {
    test('a user who cannot view the employee does not see it, an assignee and the employee do', function () {
        $stranger = userWithRole('Team Member', $this->outsiderEmployeeId);
        $assignee = userWithRole('Team Member', $this->assigneeEmployeeId);
        $subject = userWithRole('Team Member', $this->subjectEmployeeId);

        $this->actingAs($stranger);
        expect(Task::query()->whereKey($this->employeeTask->id)->exists())->toBeFalse()
            ->and($stranger->can('view', $this->employeeTask))->toBeFalse();

        $this->actingAs($assignee);
        expect(Task::query()->whereKey($this->employeeTask->id)->exists())->toBeTrue()
            ->and($assignee->can('view', $this->employeeTask))->toBeTrue();

        $this->actingAs($subject);
        expect(Task::query()->whereKey($this->employeeTask->id)->exists())->toBeTrue();
    });

    test('a user who may view employee records sees it, for scoped and unscoped roles alike', function () {
        Permission::findOrCreate('employees.view');

        $viewer = userWithRole('Team Member', $this->outsiderEmployeeId);
        $viewer->givePermissionTo('employees.view');

        foreach (['Team Member', 'Managers'] as $role) {
            $viewer->syncRoles([$role]);

            $this->actingAs($viewer);
            expect(Task::query()->whereKey($this->employeeTask->id)->exists())->toBeTrue($role);
        }
    });

    test('an unscoped role without employee access does not see it until it raised it', function () {
        $manager = userWithRole('Managers', $this->outsiderEmployeeId);
        $this->actingAs($manager);
        expect(Task::query()->whereKey($this->employeeTask->id)->exists())->toBeFalse()
            ->and(Task::query()->whereKey($this->departmentTask->id)->exists())->toBeTrue();

        $this->employeeTask->forceFill(['created_by' => $manager->id])->save();
        expect(Task::query()->whereKey($this->employeeTask->id)->exists())->toBeTrue();
    });
});

test('a scoped user sees a task raised on their own department but not on another', function () {
    $member = userWithRole('Public Relations', $this->memberEmployeeId);
    $otherTask = Task::factory()->create(['title' => 'Other department task', 'source_type' => 'department', 'source_id' => $this->otherDepartmentId]);
    $otherTask->taskAssignees()->delete();
    $otherTask->taskAssignees()->create(['employee_id' => $this->assigneeEmployeeId]);

    $this->actingAs($member);

    expect(Task::query()->whereKey($this->departmentTask->id)->exists())->toBeTrue()
        ->and(Task::query()->whereKey($otherTask->id)->exists())->toBeFalse()
        ->and($member->can('update', $this->departmentTask))->toBeTrue();
});

describe('my tasks', function () {
    test('forUser is the top-level tasks the user is assigned to or raised, and the desk agrees', function () {
        $user = userWithRole('Managers', $this->assigneeEmployeeId);
        $this->actingAs($user);

        $raised = Task::factory()->create(['created_by' => $user->id]);
        $raised->taskAssignees()->delete();
        $subtask = Task::factory()->create(['parent_task_id' => $this->employeeTask->id, 'source_type' => 'employee', 'source_id' => $this->subjectEmployeeId]);
        $subtask->taskAssignees()->delete();
        $subtask->taskAssignees()->create(['employee_id' => $this->assigneeEmployeeId]);
        $unrelated = Task::factory()->create();
        $unrelated->taskAssignees()->delete();

        $mine = Task::query()->forUser($user)->pluck('id')->all();

        expect($mine)->toContain($raised->id, $this->employeeTask->id, $this->departmentTask->id)
            ->not->toContain($subtask->id, $unrelated->id)
            ->and(Task::query()->involving($user)->pluck('id')->all())->toContain($subtask->id)
            ->and(Task::query()->assignedTo($user)->pluck('id')->all())->not->toContain($raised->id)
            ->and(Task::query()->createdBy($user)->pluck('id')->all())->toBe([$raised->id])
            ->and(DeskWork::myTasks($user)->pluck('id')->sort()->values()->all())->toBe(collect($mine)->sort()->values()->all());
    });

    test('a user without an employee only has the tasks they raised', function () {
        $user = userWithRole('Managers');
        $this->actingAs($user);
        $raised = Task::factory()->create(['created_by' => $user->id]);

        expect(Task::query()->forUser($user)->pluck('id')->all())->toBe([$raised->id])
            ->and(Task::query()->assignedTo($user)->exists())->toBeFalse();
    });

    test('awaitingReviewBy is the tasks the user raised that are under review', function () {
        $user = userWithRole('Managers');
        $this->actingAs($user);
        $review = Task::factory()->create(['created_by' => $user->id, 'status' => TaskStatus::UnderReview]);
        Task::factory()->create(['created_by' => $user->id, 'status' => TaskStatus::Todo]);

        expect(Task::query()->awaitingReviewBy($user)->pluck('id')->all())->toBe([$review->id]);
    });
});
